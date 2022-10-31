<?php


namespace App\TgBot;


use GuzzleHttp\Client;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TgBotApi\BotApiBase\ApiClient;
use TgBotApi\BotApiBase\ApiClientInterface;
use TgBotApi\BotApiBase\BotApiRequestInterface;
use TgBotApi\BotApiBase\Type\InputFileType;
use Zend\Diactoros\Request;
use Zend\Diactoros\Stream;
use function GuzzleHttp\Psr7\str;

class TgApiClient implements ApiClientInterface
{
    private $client;

    /**
     * @var string
     */
    private $botKey;

    /**
     * @var string
     */
    private $endPoint;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * ApiApiClient constructor.
     */
    public function __construct(
        Client $client
    ) {
        $this->client = $client;
    }

    /**
     * @throws ClientExceptionInterface
     *
     * @return mixed
     */
    public function send(string $method, BotApiRequestInterface $apiRequest)
    {
        $request = new Request($this->generateUri($method), 'POST');

        $boundary = \uniqid('', true);

        $stream = new Stream( fopen(sprintf('data://text/plain,%s', $this->createStreamBody($boundary, $apiRequest)), 'r'));

        $response = $this->client->send($request
            ->withHeader('Content-Type', 'multipart/form-data; boundary="' . $boundary . '"')
            ->withBody($stream));

        $content = $response->getBody()->getContents();

        return \json_decode($content, false);
    }

    public function setBotKey(string $botKey): void
    {
        $this->botKey = $botKey;
    }

    public function setEndpoint(string $endPoint): void
    {
        $this->endPoint = $endPoint;
    }

    protected function generateUri(string $method): string
    {
        return \sprintf(
            '%s/bot%s/%s',
            $this->endPoint,
            $this->botKey,
            $method
        );
    }

    /**
     * @param mixed $boundary
     */
    protected function createStreamBody($boundary, BotApiRequestInterface $request): string
    {
        $stream = '';
        foreach ($request->getData() as $name => $value) {
            if (is_array($value)) {
                $value  = json_encode($value);
            }
            // todo [GreenPlugin] fix type cast and replace it to normalizer
            $stream .= $this->createDataStream($boundary, $name, (string) $value);
        }

        foreach ($request->getFiles() as $name => $file) {
            $stream .= $this->createFileStream($boundary, $name, $file);
        }

        return '' !== $stream ? $stream . "--$boundary--\r\n" : '';
    }

    /**
     * @param $boundary
     * @param $name
     */
    protected function createFileStream($boundary, $name, InputFileType $file): string
    {
        $headers = \sprintf(
            "Content-Disposition: form-data; name=\"%s\"; filename=\"%s\"\r\n",
            $name,
            $file->getBasename()
        );
        $headers .= \sprintf("Content-Length: %s\r\n", (string) $file->getSize());
        $headers .= \sprintf("Content-Type: %s\r\n", \mime_content_type($file->getRealPath()));

        $streams = "--$boundary\r\n$headers\r\n";
        $streams .= \file_get_contents($file->getRealPath());
        $streams .= "\r\n";

        return $streams;
    }

    /**
     * @param $boundary
     * @param $name
     * @param $value
     */
    protected function createDataStream(string $boundary, string $name, string $value): string
    {
        $headers = \sprintf("Content-Disposition: form-data; name=\"%s\"\r\n", $name);
        $headers .= \sprintf("Content-Length: %s\r\n", (string) \strlen($value));

        return "--$boundary\r\n$headers\r\n$value\r\n";
    }
}
