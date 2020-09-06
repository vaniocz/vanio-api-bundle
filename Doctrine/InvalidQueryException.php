<?php
namespace Vanio\ApiBundle\Doctrine;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Vanio\Stdlib\Strings;

class InvalidQueryException extends \Exception implements HttpExceptionInterface
{
    public const CODE_INVALID_DQL = 1;
    public const CODE_INVALID_SQL = 2;
    public const CODE_FORBIDDEN = 3;
    public const CODE_UNKNOWN_FIELD = 4;
    public const CODE_UNKNOWN_ASSOCIATION = 5;

    public static function forbidden(string $message): self
    {
        return new static(sprintf('Invalid DQL query: %s.', $message), self::CODE_FORBIDDEN);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    /**
     * @return mixed[]
     */
    public function getHeaders(): array
    {
        return [];
    }

    public static function invalidDqlCondition(QueryException $exception, int $conditionOffset): \Throwable
    {
        return self::unknownField($exception)
            ?: self::invalidDql($exception, $conditionOffset)
            ?: $exception;
    }

    public static function invalidSql(DriverException $exception): \Throwable
    {
        if (preg_match('~invalid input syntax for type .+: ".+"~', $exception->getMessage(), $matches)) {
            return new static(sprintf('%s.', ucfirst($matches[0])), self::CODE_INVALID_SQL, $exception);
        }

        return $exception;
    }

    private static function unknownField(QueryException $exception): ?self
    {
        $message = $exception->getMessage();

        if (preg_match('~Class (.+) has no ((?:field or )?association) named (.*)~', $message, $matches)) {
            return new static(
                sprintf('The entity %s has no %s "%s".', Strings::baseName($matches[1]), $matches[2], $matches[3]),
                $matches[2] === 'association' ? self::CODE_UNKNOWN_ASSOCIATION : self::CODE_UNKNOWN_FIELD,
                $exception
            );
        }

        return null;
    }

    private static function invalidDql(QueryException $exception, int $conditionOffset): ?self
    {
        $pattern = '~^\[(Syntax|Semantical) Error\](?: line 0, col (-?\d+))?( near \'.+\')?:?(?: Error:)?\s*(.*)~';

        if (!preg_match($pattern, $exception->getMessage(), $matches)) {
            return null;
        }

        $message = sprintf('%s error in DQL query', $matches[1]);

        if ($matches[2] && $matches[2] >= $conditionOffset) {
            $message = sprintf('%s at offset %d', $message, $matches[2] - $conditionOffset);
        } elseif ($matches[2] && $matches[2] !== -1) {
            return null;
        }

        $message .= $matches[3];
        $message .= sprintf(': %s.', str_replace(Lexer::class . '::', '', $matches[4]));

        return new static($message, self::CODE_INVALID_DQL, $exception);
    }
}
