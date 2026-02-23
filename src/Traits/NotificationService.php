<?php

namespace Rivalex\Lingua\Traits;

use Flux\Flux;
use Illuminate\Support\Facades\Log;

trait NotificationService
{
    public const string SUCCESS = 'success';
    public const string WARNING = 'warning';
    public const string ERROR = 'danger';
    public const string POSITION = 'top end';
    public const int DELAY = 3000;

    public function success(?string $title = null, ?string $description = null, string $position = self::POSITION, bool $log = false, int $delay = self::DELAY): void
    {
        $this->notification(title: $title, description: $description, position: $position, log: $log, delay: $delay);
    }

    public function warning(?string $title = null, ?string $description = null, string $position = self::POSITION, bool $log = false, int $delay = self::DELAY): void
    {
        $this->notification(type: self::WARNING, title: $title, description: $description, position: $position, log: $log, delay: $delay);
    }

    public function error(?string $title = null, ?string $description = null, string $position = self::POSITION, bool $log = true, int $delay = 0): void
    {
        $this->notification(type: self::ERROR, title: $title, description: $description, position: $position, log: $log, delay: $delay);
    }

	public function notification(
        ?string $type = self::SUCCESS,
        ?string $title = null,
        ?string $description = null,
        ?string $position = self::POSITION,
        bool $log = false,
        ?int $delay = self::DELAY,
    ): void
    {
        if($log) {
            Log::error($title . ': ' . $description . ' - Caused by user:' . auth()->user()->getAuthIdentifier());
        }

        Flux::toast(
            text: $description,
            heading: $title,
            duration: $delay,
            variant: $type,
            position: $position
        );
	}

}
