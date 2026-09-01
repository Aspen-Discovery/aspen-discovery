<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;

class OverDriveCallUrlResponseValidationTest extends TestCase
{
    /**
     * Helper to test the response validation logic extracted from _callUrl().
     */
    private function validateResponse(?string $content): ?object
    {
        $response = json_decode($content);
        $validResponse = $response && !($response->errorCode || $response->message === 'An unexpected error has occurred.');
        return $validResponse ? $response : null;
    }

    // ============================================
    // VALID RESPONSE TESTS
    // ============================================

    public function testMinimalValidResponse(): void
    {
        $json = '{"id":"123","title":"Test Book"}';
        $result = $this->validateResponse($json);

        $this->assertNotNull($result);
        $this->assertSame('123', $result->id);
    }

    public function testValidWithMessageProperty(): void
    {
        $json = '{"id":"456","title":"Another Book","message":"Success"}';
        $result = $this->validateResponse($json);

        $this->assertNotNull($result);
        $this->assertSame('Success', $result->message);
    }

    public function testValidWithNullErrorCodeAndNormalMessage(): void
    {
        $json = '{"id":"789","title":"Book","message":"Operation successful","errorCode":null}';
        $result = $this->validateResponse($json);

        $this->assertNotNull($result);
        $this->assertNull($result->errorCode);
    }

    #[TestWith(['{"id":"1000","errorCode":false}'])]
    #[TestWith(['{"id":"1001","errorCode":0}'])]
    #[TestWith(['{"id":"999","errorCode":""}'])]
    #[TestWith(['{"id":"123","errorCode":0.0}'])]
    public function testValidWithFalsyErrorCodes(string $json): void
    {
        $this->assertNotNull($this->validateResponse($json));
    }

    public function testValidWithNestedObjects(): void
    {
        $json = '{"id":"e3d00783-30d7-4bad-a74f-f761c2f69cc0","title":"Queste","creators":{"role":"Author","name":"Angie Sage"}}';
        $result = $this->validateResponse($json);

        $this->assertNotNull($result);
        $this->assertSame('Angie Sage', $result->creators->name);
    }

    // ============================================
    // INVALID RESPONSE TESTS (Data Provider Attribute)
    // ============================================

    #[DataProvider('invalidResponseProvider')]
    public function testInvalidResponses(string $json): void
    {
        $result = $this->validateResponse($json);
        $this->assertNull($result);
    }

    public static function invalidResponseProvider(): array
    {
        return [
            'Non-empty errorCode string'                     => ['{"errorCode":"TitleNotFoundError","message":"The requested title was not found."}'],
            'errorCode integer 1'                             => ['{"id":"456","title":"Book","message":"Success","errorCode":1}'],
            'errorCode boolean true'                          => ['{"id":"999","title":"Book","message":"Success","errorCode":true}'],
            'errorCode negative integer'                      => ['{"id":"123","title":"Book","errorCode":-1}'],
            'errorCode whitespace string'                     => ['{"id":"123","title":"Book","errorCode":" "}'],
            'errorCode object (truthy object)'                => ['{"id":"123","title":"Book","errorCode":{"code":"ERR001"}}'],
            'Authentication error payload'                   => ['{"errorCode":"UnauthorizedError","message":"Authentication failed.","status":401}'],
            'Unexpected error msg (errorCode null)'           => ['{"id":"1002","title":"Book","message":"An unexpected error has occurred.","errorCode":null}'],
            'Unexpected error msg (no errorCode field)'       => ['{"message":"An unexpected error has occurred."}'],
            'Unexpected error msg + error code'               => ['{"message":"An unexpected error has occurred.","errorCode":"500"}'],
        ];
    }

    // ============================================
    // EDGE CASES
    // ============================================

    #[TestWith(['null'])]
    #[TestWith(['{invalid json}'])]
    public function testInvalidOrEmptyInputs(?string $json): void
    {
        $this->assertNull($this->validateResponse($json));
    }
}