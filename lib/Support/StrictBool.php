<?php

declare(strict_types=1);

namespace OCA\BudgetCheck\Support;

/**
 * Explicit boolean parsing for request payloads.
 *
 * Form-encoded bodies deliver checkbox-style flags as the literal string
 * "false", and JSON clients can send "0"/"1" strings. A plain `(bool)` or
 * `!empty()` cast treats `"false"` as truthy — a silent wrong-write defect
 * class. Parse explicitly instead: known truthy/falsy spellings map to bool,
 * absent values fall back to the caller's default, and unrecognized input is
 * rejected with InvalidArgumentException (fail-closed, never silently true).
 */
final class StrictBool
{
	private const TRUTHY = ['1', 'true', 'yes', 'on'];
	private const FALSY = ['0', 'false', 'no', 'off'];

	/**
	 * Parse a single value. Returns null when the value carries no decidable
	 * meaning (null, '', unrecognized string/int) so callers can decide between
	 * "use default" and "reject".
	 */
	public static function parse(mixed $value): ?bool
	{
		if (is_bool($value)) {
			return $value;
		}
		if ($value === null || $value === '') {
			return null;
		}
		if (is_int($value)) {
			return $value === 1 ? true : ($value === 0 ? false : null);
		}
		if (is_string($value)) {
			$v = strtolower(trim($value));
			if (in_array($v, self::TRUTHY, true)) {
				return true;
			}
			if (in_array($v, self::FALSY, true)) {
				return false;
			}
		}
		return null;
	}

	/**
	 * Read an optional boolean payload field. Absent/empty → $default.
	 * Present but unparseable (e.g. "maybe", 7) → InvalidArgumentException.
	 */
	public static function field(array $payload, string $name, bool $default = false): bool
	{
		if (!array_key_exists($name, $payload)) {
			return $default;
		}
		$parsed = self::parse($payload[$name]);
		if ($parsed === null) {
			throw new \InvalidArgumentException($name . ' must be a boolean.');
		}
		return $parsed;
	}

	/**
	 * Read a required boolean payload field. Missing or unparseable →
	 * InvalidArgumentException.
	 */
	public static function requiredField(array $payload, string $name): bool
	{
		$parsed = array_key_exists($name, $payload) ? self::parse($payload[$name]) : null;
		if ($parsed === null) {
			throw new \InvalidArgumentException($name . ' is required and must be a boolean.');
		}
		return $parsed;
	}
}
