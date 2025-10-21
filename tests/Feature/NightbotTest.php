<?php

namespace Tests\Feature;

use App\Facades\NowPlaying;
use App\Models\Track;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Testing\TestResponse;
use LogicException;
use Tests\TestCase;

class NightbotTest extends TestCase
{
    protected function testRequest(string $url, array $params = [], NightbotTestOptions $options = new NightbotTestOptions): TestResponse
    {
        // NB: Headers
        $headers = [];

        if(!empty($options->nightbotHeaderFields))
            $headers['X-Nightbot-User'] = http_build_query($options->nightbotHeaderFields);

        // NB: Body
        if($options->queryParamToken === null)
            $params['token'] = config('nightbot.api_token');
        else if($options->queryParamToken !== false)
            $params['token'] = $options->queryParamToken;

        // NB: Config
        if($options->removeConfigApiToken)
            config(['nightbot.api_token' => null]);

        // NB: Make the request
        $qstr = http_build_query($params);

        $uri = "/api/nightbot{$url}?$qstr";

        return $this->get($uri, $headers);
    }

    public function testNoApiTokenYieldsInternalServerError(): void
    {
        $this
            ->testRequest('/vote', options: new NightbotTestOptions(removeConfigApiToken: true))
            ->assertServerError();
    }

    public function testRequestWithoutApiTokenYieldsBadRequest(): void
    {
        $this
            ->testRequest('/vote', options: new NightbotTestOptions(queryParamToken: false))
            ->assertBadRequest();
    }

    public function testIncorrectApiTokenYieldsUnauthorized(): void
    {
        $this
            ->testRequest('/vote', options: new NightbotTestOptions(queryParamToken: 'incorrect'))
            ->assertUnauthorized();
    }

    public function testRequestWithoutNightbotHeaderYieldsBadRequest(): void
    {
        $this
            ->testRequest('/vote', options: new NightbotTestOptions(nightbotHeaderFields: false))
            ->assertBadRequest();
    }

    public function testRequestWithoutDisplayNameYieldsBadRequest(): void
    {
        $this
            ->testRequest('/vote', options: new NightbotTestOptions(nightbotHeaderFields: [
                'without' => 'any-display-name'
            ]))
            ->assertBadRequest();
    }

    public function testVoteWithoutTypeYieldsBadRequest(): void
    {
        $this
            ->testRequest('/vote')
            ->assertBadRequest();
    }

    public function testVoteWithInvalidTypeYieldsUnprocessableEntity(): void
    {
        $this
            ->testRequest('/vote', [
                'type' => 'bad'
            ])
            ->assertUnprocessable();
    }

    private function testVoteSuccessful(string $type): void
    {
        NowPlaying::fake();

        $hash = '925bf0783aa48446bfe8181686525b6e';

        try{
            $track = Track::whereHash($hash)->firstOrFail();
        }catch(ModelNotFoundException) {
            $track = Track::factory()->uploaded()->create();
            $track->update(['hash' => $hash]);
        }

        $emoji = match($type) {
            'up' => '⬆️',
            'down' => '⬇️',
            default => throw new LogicException()
        };

        $this
            ->testRequest('/vote', [
                'type' => $type
            ])
            ->assertSuccessful()
            ->assertContent("Thank you! Your vote $emoji was recorded");
        
        $this->assertEquals(1, $track->votes()->whereType($type)->count());
        $this->assertEquals('test user', $track->votes()->latest()->firstOrFail()->display_name);
    }

    public function testVoteUpSuccessful(): void
    {
        $this->testVoteSuccessful('up');
    }

    public function testVoteDownSuccessful(): void
    {
        $this->testVoteSuccessful('down');
    }

    public function testRepeatedVoteOverwritesHistoricVote(): void
    {
        $this->testVoteSuccessful('down');
        $this->testVoteSuccessful('up');
    }

    public function testSuggestSongSuccessful(): void
    {

    }

    public function testSuggestMetadataSuccessful(): void
    {

    }

    public function testRequestSongSuccessful(): void
    {

    }
}
