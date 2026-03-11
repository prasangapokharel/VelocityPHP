<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\Validator;

/**
 * @covers \App\Utils\Validator
 */
class ValidatorTest extends TestCase
{
    public function test_required_rule_fails_on_empty_value(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertTrue($v->fails());
        $this->assertNotEmpty($v->errors()['name']);
    }

    public function test_required_rule_passes_with_value(): void
    {
        $v = Validator::make(['name' => 'Prasanga'], ['name' => 'required']);
        $this->assertFalse($v->fails());
    }

    public function test_email_rule_fails_on_invalid_email(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertTrue($v->fails());
    }

    public function test_email_rule_passes_on_valid_email(): void
    {
        $v = Validator::make(['email' => 'user@example.com'], ['email' => 'email']);
        $this->assertFalse($v->fails());
    }

    public function test_min_rule_fails_when_too_short(): void
    {
        $v = Validator::make(['password' => 'abc'], ['password' => 'min:8']);
        $this->assertTrue($v->fails());
    }

    public function test_min_rule_passes_when_long_enough(): void
    {
        $v = Validator::make(['password' => 'strongpassword'], ['password' => 'min:8']);
        $this->assertFalse($v->fails());
    }

    public function test_max_rule_fails_when_too_long(): void
    {
        $v = Validator::make(['username' => str_repeat('a', 256)], ['username' => 'max:255']);
        $this->assertTrue($v->fails());
    }

    public function test_max_rule_passes_when_within_limit(): void
    {
        $v = Validator::make(['username' => 'prasanga'], ['username' => 'max:255']);
        $this->assertFalse($v->fails());
    }

    public function test_url_rule_fails_on_invalid_url(): void
    {
        $v = Validator::make(['website' => 'not a url'], ['website' => 'url']);
        $this->assertTrue($v->fails());
    }

    public function test_url_rule_passes_on_valid_url(): void
    {
        $v = Validator::make(['website' => 'https://example.com'], ['website' => 'url']);
        $this->assertFalse($v->fails());
    }

    public function test_numeric_rule_fails_on_non_numeric(): void
    {
        $v = Validator::make(['age' => 'abc'], ['age' => 'numeric']);
        $this->assertTrue($v->fails());
    }

    public function test_numeric_rule_passes_on_number(): void
    {
        $v = Validator::make(['age' => '25'], ['age' => 'numeric']);
        $this->assertFalse($v->fails());
    }

    public function test_multiple_rules_pipe_separated(): void
    {
        $v = Validator::make(['email' => ''], ['email' => 'required|email']);
        $this->assertTrue($v->fails());
        $errors = $v->errors()['email'];
        $this->assertCount(1, $errors); // only 'required' fires since value is empty
    }

    public function test_in_rule_fails_when_not_in_list(): void
    {
        $v = Validator::make(['role' => 'superadmin'], ['role' => 'in:admin,user,editor']);
        $this->assertTrue($v->fails());
    }

    public function test_in_rule_passes_when_in_list(): void
    {
        $v = Validator::make(['role' => 'admin'], ['role' => 'in:admin,user,editor']);
        $this->assertFalse($v->fails());
    }

    public function test_same_rule_fails_when_values_differ(): void
    {
        $v = Validator::make(
            ['password' => 'abc123', 'confirm' => 'different'],
            ['confirm' => 'same:password']
        );
        $this->assertTrue($v->fails());
    }

    public function test_same_rule_passes_when_values_match(): void
    {
        $v = Validator::make(
            ['password' => 'abc123', 'confirm' => 'abc123'],
            ['confirm' => 'same:password']
        );
        $this->assertFalse($v->fails());
    }

    public function test_sanitize_strips_html_from_string(): void
    {
        $result = Validator::sanitize('<script>alert("xss")</script>Hello', 'string');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_sanitize_email_removes_invalid_chars(): void
    {
        $result = Validator::sanitize('user @example.com', 'email');
        $this->assertStringNotContainsString(' ', $result);
    }

    public function test_custom_error_messages(): void
    {
        $v = Validator::make(
            ['name' => ''],
            ['name' => 'required'],
            ['name.required' => 'Your name is required!']
        );
        $v->validate();
        $this->assertSame('Your name is required!', $v->errors()['name'][0]);
    }
}
