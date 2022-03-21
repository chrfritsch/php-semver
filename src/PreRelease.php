<?php

namespace z4kn4fein\SemVer;

class PreRelease
{
    /** @var array */
    private $preReleaseParts;

    /**
     * PreRelease constructor.
     * @param array $preReleaseParts The pre-release parts.
     */
    private function __construct(array $preReleaseParts)
    {
        $this->preReleaseParts = $preReleaseParts;
    }

    /**
     * @return PreRelease The incremented pre-release.
     */
    public function increment(): PreRelease
    {
        $result = $this->copy();
        $lastNumericIndex = 0;
        foreach ($result->preReleaseParts as $key => $part) {
            if (is_numeric($part)) {
                $lastNumericIndex = $key;
            }
        }

        if ($lastNumericIndex != 0) {
            $result->preReleaseParts[$lastNumericIndex] = intval($result->preReleaseParts[$lastNumericIndex]) + 1;
        } else {
            $result->preReleaseParts[] = 0;
        }

        return $result;
    }

    /**
     * @return string The string representation of the pre-release.
     */
    public function __toString()
    {
        return implode('.', $this->preReleaseParts);
    }

    /**
     * @return PreRelease The copied pre-release.
     */
    private function copy(): PreRelease
    {
        return new PreRelease($this->preReleaseParts);
    }

    /**
     * @throws VersionFormatException When the $preReleaseString is invalid.
     */
    private function validate(): void
    {
        foreach ($this->preReleaseParts as $part) {
            if ($this->hasOnlyNumbers($part) && strlen($part) > 1 && $part[0] == "0") {
                throw new VersionFormatException(sprintf(
                    "The pre-release part '%s' is numeric but contains a leading zero.",
                    $part
                ));
            }

            if (!$this->hasOnlyAlphanumericsAndHyphen($part)) {
                throw new VersionFormatException(sprintf(
                    "The pre-release part '%s' contains invalid character.",
                    $part
                ));
            }
        }
    }

    /**
     * @param string $part part to check.
     * @return bool True when the part has only numbers.
     */
    private function hasOnlyNumbers(string $part): bool
    {
        return preg_match("/^[0-9]+$/", $part);
    }

    /**
     * @param string $part The part to check.
     * @return bool True when the part has only alphanumerics.
     */
    private function hasOnlyAlphanumericsAndHyphen(string $part): bool
    {
        return preg_match("/^[0-9A-Za-z-]+$/", $part);
    }

    /**
     * Creates a new pre-release tag with initial default value (-0).
     *
     * @return PreRelease The default pre-release tag.
     */
    public static function createDefault(): PreRelease
    {
        return new PreRelease([0]);
    }

    /**
     * @param string $preReleaseString The pre-release string.
     * @return PreRelease The parsed pre-release part.
     * @throws VersionFormatException When the given pre-release string is invalid.
     */
    public static function parse(string $preReleaseString): PreRelease
    {
        $preReleaseString = trim($preReleaseString);
        if ($preReleaseString == null || $preReleaseString == "") {
            throw new VersionFormatException("preReleaseString cannot be empty.");
        }

        $preRelease = new PreRelease(explode('.', $preReleaseString));
        $preRelease->validate();

        return $preRelease;
    }

    /**
     * @param string|PreRelease $p1 The left side of the comparison.
     * @param string|PreRelease $p2 The right side of the comparison.
     * @return int -1 when $p1 < $p2, 0 when $p1 == $p2, 1 when $p1 > $p2.
     * @throws VersionFormatException When the given pre-release values are invalid.
     */
    public static function compare($p1, $p2): int
    {
        if (!$p1 instanceof PreRelease) {
            $p1 = self::parse($p1);
        }

        if (!$p2 instanceof PreRelease) {
            $p2 = self::parse($p2);
        }

        $v1Size = count($p1->preReleaseParts);
        $v2Size = count($p2->preReleaseParts);

        $count = $v1Size > $v2Size ? $v2Size : $v1Size;

        for ($i = 0; $i < $count; $i++) {
            $part = self::comparePart($p1->preReleaseParts[$i], $p2->preReleaseParts[$i]);
            if ($part != 0) {
                return $part;
            }
        }

        return Utils::comparePrimitive($v1Size, $v2Size);
    }

    /**
     * @param mixed $a The left side of the comparison.
     * @param mixed $b The right side of the comparison.
     * @return int -1 when $a < $b, 0 when $a == $b, 1 when $v1 > $b.
     */
    private static function comparePart($a, $b): int
    {
        if (is_numeric($a) && !is_numeric($b)) {
            return -1;
        }

        if (!is_numeric($a) && is_numeric($b)) {
            return 1;
        }

        return is_numeric($a) && is_numeric($b)
            ? Utils::comparePrimitive(intval($a), intval($b))
            : Utils::comparePrimitive($a, $b);
    }
}
