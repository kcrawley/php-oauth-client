<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace fkooman\OAuth\Client;

use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;

use fkooman\OAuth\Common\Scope;

class CallbackTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $clientConfig;

    public function setUp()
    {
        $this->clientConfig = array();

        $this->clientConfig[] = new ClientConfig(
            array(
                "client_id" => "foo",
                "client_secret" => "bar",
                "authorize_endpoint" => "http://www.example.org/authorize",
                "token_endpoint" => "http://www.example.org/token"
            )
        );
    }

    public function testCallback()
    {
        $client = new Client();
        $mock = new MockPlugin();
        $mock->addResponse(
            new Response(
                200,
                null,
                json_encode(
                    array(
                        "access_token" => "my_access_token",
                        "token_type" => "BeArEr",
                        "refresh_token" => "why_not_a_refresh_token"
                    )
                )
            )
        );
        $client->addSubscriber($mock);

        $baseStorage = new BaseStorage();

        $state = new State(
            array(
                "state" => "my_state",
                "client_config_id" => "foo",
                "issue_time" => time() - 100,
                "user_id" => "my_user_id",
                "scope" => array("foo", "bar")
            )
        );
        $baseStorage->storeState($state);

        $callback = new Callback("foo", $this->clientConfig[0], $baseStorage, $client);

        $tokenResponse = $callback->handleCallback(
            array(
                "state" => "my_state",
                "code" => "my_code"
            ),
            new Context("my_user_id", array("foo", "bar"))
        );

        $this->assertEquals("my_access_token", $tokenResponse->getAccessToken());
    }
}
