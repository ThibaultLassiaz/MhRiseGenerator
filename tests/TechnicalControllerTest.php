<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TechnicalTestController extends WebTestCase {

    public function testGetRessource(): void
    {
        $client = static::createClient();

        $client->request('GET', '/home');

        $response = $client->getResponse()->getContent();
        $decoded_response = json_decode($response);

        $this->assertResponseIsSuccessful();
        $this->assertEquals($decoded_response->Title, 'Test');
    }

    public function testGetFizzBuzz(): void
    {
        $client = static::createClient();

        $client->request('GET', '/fizzbuzz?number=15');

        $response = $client->getResponse()->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertEquals($response, 'FizzBuzz');
    }
}