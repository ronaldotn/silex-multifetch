<?php

namespace Marmelab\MultifetchTest;

use Marmelab\Multifetch\MultifetchServiceProvider;
use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultifetchServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    private $fixture = array();

    protected function setUp()
    {
        $this->fixture = array(
            'one' => array(
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array(
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_1_1":"value_1_1","field_2":2}',
            ),
            'two' => array (
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array (
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_2_1":"value_2_1","field_2":3}',
            ),
        );
    }

    public function testMultifetchGet()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2'));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchPost()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $content = json_encode(array('one' => '/url1', 'two' => '/url2'));
        $server = array('CONTENT_TYPE' => 'application/json');
        $request = Request::create('/multi/', 'POST', array(), array(), array(), $server, $content);
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchCustomUrl()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET', 'POST'),
            'multifetch.url' => '',
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/', 'GET', array('one' => '/url1', 'two' => '/url2'));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchRespectOrder()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url2', 'two' => '/url1'));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals(array(
            'one' => $this->fixture['two'],
            'two' => $this->fixture['one'],
        ), $responses);
    }

    public function testMultifetchParallelConfig()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
            'multifetch.parallel' => 1,
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2'));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchParallelParameter()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2', '_parallel' => 1));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchParallelConfigOveriden()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
            'multifetch.parallel' => 1,
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2', '_parallel' => 0));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture, $responses);
    }

    public function testMultifetchRequestsError()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $app->get('/url-alpha', function () use ($app) {
            throw new \Exception('This is alpha ;)');
        });

        $content = json_encode(array('one' => '/url1', 'two' => '/url2', 'three' => '/url3', 'four' => '/url-alpha'));
        $server = array('CONTENT_TYPE' => 'application/json');
        $request = Request::create('/multi/', 'POST', array(), array(), array(), $server, $content);
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals($this->fixture + array(
            'three' => array(
                'code' => 404,
                'headers' => array(),
                'body' => '{"error":"No route found for \"GET \/url3\"","type":"NotFoundHttpException"}',
            ),
            'four' => array(
                'code' => 500,
                'headers' => array(),
                'body' => '{"error":"This is alpha ;)","type":"InternalServerError"}',
            ),
        ), $responses);
    }

    public function testMultifetchWithoutHeaders()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.methods' => array('GET'),
            'multifetch.headers' => false,
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2'));
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals(array(
            'one' => array(
                'code' => 200,
                'body' => '{"field_1_1":"value_1_1","field_2":2}',
            ),
            'two' => array (
                'code' => 200,
                'body' => '{"field_2_1":"value_2_1","field_2":3}',
            ),
        ), $responses);
    }

    private function getReponsesAsArray(Response $response)
    {
        $responses = json_decode($response->getContent(), true);
        foreach ($responses as $key => $singleResponse) {
            if (!isset($singleResponse['headers'])) {
                continue;
            }

            $headers = array();
            foreach ($singleResponse['headers'] as $header) {
                if ('date' === $header['name']) {
                    continue;
                }

                $headers[] = $header;
            }
            $responses[$key]['headers'] = $headers;
        }

        return $responses;
    }
}
