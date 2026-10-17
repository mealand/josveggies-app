<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\BoxTemplate;
use App\Models\Run;
use Illuminate\Contracts\Session\Session;

/**
 * A session cart for one Run. Holds only ids and quantities; prices are always read from the
 * database at checkout, never trusted from the client.
 */
class Cart
{
    private const KEY = 'cart';

    public const MAX_QTY_PER_LINE = 10;

    public function __construct(private Session $session) {}

    /**
     * @return array{run_id: int|null, boxes: array<int, int>, add_ons: array<int, int>}
     */
    public function raw(): array
    {
        /** @var array{run_id?: int|null, boxes?: array<int, int>, add_ons?: array<int, int>} $cart */
        $cart = $this->session->get(self::KEY, []);

        return [
            'run_id' => $cart['run_id'] ?? null,
            'boxes' => $cart['boxes'] ?? [],
            'add_ons' => $cart['add_ons'] ?? [],
        ];
    }

    public function setQuantity(Run $run, string $type, int $id, int $quantity): void
    {
        $cart = $this->raw();

        if ($cart['run_id'] !== $run->id) {
            $cart = ['run_id' => $run->id, 'boxes' => [], 'add_ons' => []];
        }

        $bucket = $type === 'box' ? 'boxes' : 'add_ons';
        $quantity = max(0, min(self::MAX_QTY_PER_LINE, $quantity));

        if ($quantity === 0) {
            unset($cart[$bucket][$id]);
        } else {
            $cart[$bucket][$id] = $quantity;
        }

        $this->session->put(self::KEY, $cart);
    }

    public function add(Run $run, string $type, int $id): void
    {
        $bucket = $type === 'box' ? 'boxes' : 'add_ons';
        $current = $this->raw()['run_id'] === $run->id ? ($this->raw()[$bucket][$id] ?? 0) : 0;
        $this->setQuantity($run, $type, $id, $current + 1);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }

    public function boxCount(): int
    {
        return array_sum($this->raw()['boxes']);
    }

    public function itemCount(): int
    {
        $cart = $this->raw();

        return array_sum($cart['boxes']) + array_sum($cart['add_ons']);
    }

    /**
     * Resolve the cart into priced lines for the given Run, dropping anything not offered.
     *
     * @return array{lines: list<array{type: string, id: int, name: string, quantity: int, unit_price_kobo: int, line_total_kobo: int}>, subtotal_kobo: int, box_count: int}
     */
    public function summary(?Run $run): array
    {
        $cart = $this->raw();
        $lines = [];

        if ($run === null || $cart['run_id'] !== $run->id) {
            return ['lines' => [], 'subtotal_kobo' => 0, 'box_count' => 0];
        }

        $boxes = $run->boxTemplates()->where('is_active', true)->whereIn('box_templates.id', array_keys($cart['boxes']))->get();
        foreach ($boxes as $box) {
            /** @var BoxTemplate $box */
            $qty = $cart['boxes'][$box->id];
            $lines[] = ['type' => 'box', 'id' => $box->id, 'name' => $box->name, 'quantity' => $qty, 'unit_price_kobo' => $box->price_kobo, 'line_total_kobo' => $box->price_kobo * $qty];
        }

        $addOns = $run->addOns()->where('is_active', true)->whereIn('add_ons.id', array_keys($cart['add_ons']))->get();
        foreach ($addOns as $addOn) {
            /** @var AddOn $addOn */
            $qty = $cart['add_ons'][$addOn->id];
            $lines[] = ['type' => 'add_on', 'id' => $addOn->id, 'name' => $addOn->name, 'quantity' => $qty, 'unit_price_kobo' => $addOn->price_kobo, 'line_total_kobo' => $addOn->price_kobo * $qty];
        }

        return [
            'lines' => $lines,
            'subtotal_kobo' => array_sum(array_column($lines, 'line_total_kobo')),
            'box_count' => array_sum(array_map(fn (array $l): int => $l['type'] === 'box' ? $l['quantity'] : 0, $lines)),
        ];
    }
}
