<?php

namespace App\Support;

use RuntimeException;

/**
 * Gera arquivos .zip só com PHP (zlib), sem depender da extensão zip do servidor.
 * Os arquivos são comprimidos em blocos, então funciona com backups grandes sem estourar a memória.
 */
class ZipWriter
{
    /** @var resource */
    private $out;

    private array $entries = [];

    public function __construct(private readonly string $path)
    {
        $out = @fopen($path, 'wb');
        if (! $out) {
            throw new RuntimeException("Não foi possível criar {$path}.");
        }
        $this->out = $out;
    }

    public function addFromString(string $name, string $contents): void
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        $this->addStream($name, $stream);
        fclose($stream);
    }

    public function addFile(string $file, string $name): void
    {
        $stream = @fopen($file, 'rb');
        if (! $stream) {
            throw new RuntimeException("Não foi possível ler {$file}.");
        }
        $this->addStream($name, $stream);
        fclose($stream);
    }

    public function close(): void
    {
        $start = ftell($this->out);
        $size = 0;

        foreach ($this->entries as $e) {
            $record = pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0x0800, 8, $e['time'], $e['date'],
                $e['crc'], $e['csize'], $e['size'], strlen($e['name']), 0, 0, 0, 0, 0, $e['offset']).$e['name'];
            fwrite($this->out, $record);
            $size += strlen($record);
        }

        $count = count($this->entries);
        fwrite($this->out, pack('VvvvvVVv', 0x06054B50, 0, 0, $count, $count, $size, $start, 0));
        fclose($this->out);
    }

    /** @param resource $stream */
    private function addStream(string $name, $stream): void
    {
        [$time, $date] = $this->dosTime(time());
        $offset = ftell($this->out);
        $header = fn (int $crc, int $csize, int $size) => pack('VvvvvvVVVvv', 0x04034B50, 20, 0x0800, 8, $time, $date, $crc, $csize, $size, strlen($name), 0).$name;

        fwrite($this->out, $header(0, 0, 0));

        $deflate = deflate_init(ZLIB_ENCODING_RAW, ['level' => 6]);
        $hash = hash_init('crc32b');
        $size = 0;
        $csize = 0;

        while (! feof($stream)) {
            $chunk = fread($stream, 1 << 20);
            if ($chunk === false || $chunk === '') {
                continue;
            }
            hash_update($hash, $chunk);
            $size += strlen($chunk);
            $data = deflate_add($deflate, $chunk, ZLIB_NO_FLUSH);
            $csize += strlen($data);
            fwrite($this->out, $data);
        }

        $data = deflate_add($deflate, '', ZLIB_FINISH);
        $csize += strlen($data);
        fwrite($this->out, $data);

        $crc = hexdec(hash_final($hash));
        $end = ftell($this->out);
        fseek($this->out, $offset);
        fwrite($this->out, $header($crc, $csize, $size));
        fseek($this->out, $end);

        $this->entries[] = compact('name', 'time', 'date', 'crc', 'csize', 'size', 'offset');
    }

    private function dosTime(int $timestamp): array
    {
        $d = getdate($timestamp);

        return [
            ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2),
            (max(0, $d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'],
        ];
    }
}
