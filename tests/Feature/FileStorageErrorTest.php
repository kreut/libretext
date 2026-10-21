<?php

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use League\Flysystem\UnableToCheckExistence;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Simulates an S3 failure (the same exception Flysystem 3 throws on a 403)
 * and checks that App\Exceptions\Handler turns it into a friendly message
 * instead of a 500 page. The exception goes straight to the handler, so the
 * app's catch-all SPA route can't intercept the request.
 */
class FileStorageErrorTest extends TestCase
{
    private string $message = "We couldn't load that file right now. Please try again, or contact us if it keeps happening.";

    private function renderS3Error(string $uri, bool $json = false): TestResponse
    {
        $server = $json ? ['HTTP_ACCEPT' => 'application/json'] : [];
        $request = Request::create($uri, 'GET', [], [], [], $server);
        $exception = UnableToCheckExistence::forLocation('uploads/question-media/missing.mp4');

        $response = $this->app->make(ExceptionHandler::class)->render($request, $exception);

        return TestResponse::fromBaseResponse($response);
    }

    #[Test]
    public function api_requests_get_a_json_error_message()
    {
        $this->renderS3Error('/api/discussion-comments/1', true)
            ->assertStatus(503)
            ->assertJson(['type' => 'error', 'message' => $this->message]);
    }

    #[Test]
    public function the_media_player_shows_its_error_page()
    {
        $this->renderS3Error('/discussion-comments/media-player/discussion-comment-id/89/is-phone/0')
            ->assertStatus(503)
            ->assertSee('load that file right now', false);
    }

    #[Test]
    public function other_pages_get_a_plain_message()
    {
        $this->renderS3Error('/some-page')
            ->assertStatus(503)
            ->assertSee($this->message, false);
    }

    #[Test]
    public function other_errors_are_not_affected()
    {
        $request = Request::create('/api/anything', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
        $response = $this->app->make(ExceptionHandler::class)
            ->render($request, new \RuntimeException('Something unrelated'));

        $this->assertSame(500, $response->getStatusCode());
    }
}
