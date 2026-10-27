<?php

declare(strict_types=1);

test('assets route serves a shipped file', function (): void {
    $this->get('/lingua/assets/js/lingua.min.js')->assertOk();
});

test('assets route answers 404 for a stylesheet path', function (): void {
    $this->get('/lingua/assets/css/lingua.min.css')->assertNotFound();
});

test('assets route refuses a stylesheet even when the file exists', function (): void {
    $name = 'audit-'.uniqid().'.css';
    $file = dirname(__DIR__, 3).'/src/dist/js/'.$name;
    file_put_contents($file, 'body{}');

    try {
        $this->get('/lingua/assets/js/'.$name)->assertNotFound();
    } finally {
        @unlink($file);
    }
});

test('assets route answers 404 for a NUL byte, not 500', function (): void {
    $this->get('/lingua/assets/css%00x')->assertNotFound();
});

test('assets route answers 404 for path traversal', function (): void {
    $this->get('/lingua/assets/../composer.json')->assertNotFound();
    $this->get('/lingua/assets/..%2Fcomposer.json')->assertNotFound();
    $this->get('/lingua/assets/../../composer.json')->assertNotFound();
    $this->get('/lingua/assets/..%2F..%2Fcomposer.json')->assertNotFound();
});
