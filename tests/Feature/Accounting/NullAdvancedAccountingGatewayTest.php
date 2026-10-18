<?php

namespace Tests\Feature\Accounting;

use App\Services\Accounting\AdvancedAccountingGateway;
use App\Services\Accounting\NullAdvancedAccountingGateway;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

/**
 * Regression guard for the Null gateway contract: every interface method must
 * be inert AND return a value that matches its declared return type. A method
 * with a nullable/bool/array return type and an empty body makes PHP throw a
 * TypeError at call time, which the commerce callers would have to swallow.
 */
class NullAdvancedAccountingGatewayTest extends TestCase
{
    public function test_every_interface_method_returns_its_declared_type(): void
    {
        $gateway = new NullAdvancedAccountingGateway();
        $checked = 0;

        foreach ((new ReflectionClass(AdvancedAccountingGateway::class))->getMethods() as $method) {
            $args = $this->argumentsFor($method);
            $result = $gateway->{$method->getName()}(...$args);
            $type = $method->getReturnType();

            $this->assertInstanceOf(ReflectionNamedType::class, $type, $method->getName().' must declare a return type');

            if ($result === null) {
                $this->assertTrue(
                    $type->allowsNull(),
                    $method->getName().' returned null but its return type is not nullable'
                );
            } else {
                $this->assertTrue(
                    $this->valueMatchesType($result, $type),
                    $method->getName().' returned '.get_debug_type($result).', expected '.$type->getName()
                );
            }

            $checked++;
        }

        $this->assertGreaterThanOrEqual(20, $checked, 'the interface surface shrank unexpectedly');
    }

    public function test_payment_received_is_inert_and_returns_null(): void
    {
        $gateway = new NullAdvancedAccountingGateway();

        $this->assertNull($gateway->paymentReceived(new \App\Models\OrderPayment()));
    }

    public function test_the_gateway_reports_itself_as_disabled(): void
    {
        $gateway = new NullAdvancedAccountingGateway();

        $this->assertFalse($gateway->enabled());
        $this->assertFalse($gateway->readyForLivePosting());
    }

    /** Build dummy arguments for a method. Models are unsaved; no DB is touched. */
    private function argumentsFor(ReflectionMethod $method): array
    {
        $args = [];

        foreach ($method->getParameters() as $param) {
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }

            $type = $param->getType();
            $name = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';

            $args[] = match (true) {
                $name === 'string' => 'x',
                $name === 'int' => 1,
                $name === 'float' => 1.0,
                $name === 'bool' => false,
                $name === 'array' => [],
                class_exists($name) => new $name(),
                default => null,
            };
        }

        return $args;
    }

    private function valueMatchesType(mixed $value, ReflectionNamedType $type): bool
    {
        return match ($type->getName()) {
            'bool' => is_bool($value),
            'array' => is_array($value),
            'string' => is_string($value),
            'int' => is_int($value),
            default => $value instanceof ($type->getName()),
        };
    }
}
