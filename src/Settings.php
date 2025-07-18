<?php

namespace IMEdge\Config;

use IMEdge\Json\JsonSerialization;
use IMEdge\Json\JsonString;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;

use function array_key_exists;
use function ksort;

class Settings implements JsonSerialization
{
    /** @var array<string, mixed> */
    protected array $settings = [];

    /**
     * @param stdClass|array<string, mixed> $settings
     */
    public function __construct($settings = [])
    {
        foreach ((array) $settings as $property => $value) {
            $this->set($property, $value);
        }
    }

    /**
     * @param stdClass|array<string, mixed> $any
     * @return static|Settings
     */
    public static function fromSerialization($any): Settings
    {
        return new Settings($any);
    }

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): void
    {
        try {
            JsonString::encode($value);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(sprintf(
                'Failed to JSON-encode setting value (%s): %s',
                get_debug_type($value),
                $e->getMessage()
            ));
        }
        $this->settings[$name] = $value;
    }

    /**
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $name, $default = null)
    {
        if ($this->has($name)) {
            return $this->settings[$name];
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function getArray(string $name, array $default = []): array
    {
        if ($this->has($name)) {
            return (array) $this->settings[$name];
        }

        return $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function requireArray(string $name): array
    {
        return (array) $this->getRequired(($name));
    }

    public function getAsSettings(string $name, ?Settings $default = null): Settings
    {
        if ($this->has($name)) {
            if (is_array($this->settings[$name]) || $this->settings[$name] instanceof stdClass) {
                return Settings::fromSerialization($this->settings[$name]);
            }

            throw new RuntimeException(sprintf(
                'Cannot get %s as settings: %s',
                $name,
                get_debug_type($this->settings[$name])
            ));
        }

        if ($default === null) {
            return new Settings();
        }

        return $default;
    }

    /**
     * @return mixed
     */
    public function getRequired(string $name)
    {
        if ($this->has($name)) {
            return $this->settings[$name];
        }

        throw new InvalidArgumentException("Setting '$name' is not available");
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->settings);
    }

    public function equals(Settings $settings): bool
    {
        return JsonString::encode($settings) === JsonString::encode($this);
    }

    public function jsonSerialize(): stdClass
    {
        ksort($this->settings);
        return (object) $this->settings;
    }
}
