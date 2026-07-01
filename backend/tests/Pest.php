<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/**
 * Assert the standard API envelope shape on a response.
 */
function assertEnvelope($response, bool $success = true): void
{
    $response->assertJsonStructure(['success', 'data', 'message']);
    expect($response->json('success'))->toBe($success);
}
