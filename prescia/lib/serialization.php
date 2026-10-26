<?php

declare(strict_types=1);

/**
 * Decode legacy serialized data without instantiating attacker-controlled classes.
 * Invalid payloads return false, matching the legacy decoder's semantics.
 *
 * @param mixed $payload
 * @param bool|array<int, class-string> $allowedClasses
 * @return mixed
 */
function presciaSafeUnserialize(mixed $payload, bool|array $allowedClasses = false): mixed
{
	if (!is_string($payload) || $payload === '') {
		return false;
	}

	return @unserialize($payload, ['allowed_classes' => $allowedClasses]);
}
