<?php

namespace BuckhamDuffy\BdSupport\Helpers;

use stdClass;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use BuckhamDuffy\Expressions\Value\Value;
use BuckhamDuffy\Expressions\Language\Alias;
use BuckhamDuffy\Expressions\Language\CaseRule;
use BuckhamDuffy\Expressions\Language\CaseGroup;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use BuckhamDuffy\Expressions\Operator\Comparison\Between;

class PeriodDb
{
	public function __construct(private Period $period)
	{
	}

	public function setPeriod(Period $period)
	{
		$this->period = $period;

		return $this;
	}

	public function toDb(): array
	{
		return [
			$this->period->getFrom()->startOfDay()->toDateTimeString(),
			$this->period->getTo()->endOfDay()->toDateTimeString()
		];
	}

	public function toSelect(string $column): Expression
	{
		return new Alias(
			new CaseGroup(
				collect($this->period->iterate())
					->map(function(array $period, int $index) use ($column) {
						return new CaseRule(
							new Value($this->period->getPeriodIndex($index)),
							new Between(
								$column,
								new Value($period[0]->toDateTimeString()),
								new Value($period[1]->toDateTimeString()),
							)
						);
					})
					->values()
					->all()
			),
			'period',
		);
	}

	public function toSelectArray(string $column, Builder $q): array
	{
		return collect($this->period->iterate())
			->mapWithKeys(function(array $period, int $index) use ($q, $column) {
				return [
					$this->period->getPeriodIndex($index) => $q->clone()->whereBetween(
						$column,
						[$period[0]->toDateTimeString(), $period[1]->toDateTimeString()]
					)
				];
			})
			->merge([
				'count' => $q->clone()->whereBetween($column, $this->period->toDb())
			])
			->all();
	}

	public function toSelectArrayCumulative(string $column, Builder $q): array
	{
		return collect($this->period->iterate())
			->mapWithKeys(function(array $period, int $index) use ($q, $column) {
				return [
					$this->period->getPeriodIndex($index) => $q->clone()->where(
						$column,
						'<=',
						$period[1]->toDateTimeString()
					)
				];
			})
			->merge([
				'count' => $q->clone()->whereBetween($column, $this->period->toDb())
			])
			->all();
	}

	/**
	 * @return int[]
	 */
	public function pluckData(Collection $data, string $column): array
	{
		return array_map(function(string $period) use ($data, $column) {
			return (int) $data->where('period', $period)->sum($column);
		}, $this->period->keys());
	}

	/**
	 * @return int[]
	 */
	public function pluckColumns(mixed $data): array
	{
		return array_map(function(string $period) use ($data) {
			if ($data instanceof Collection) {
				return (int) $data->sum($period);
			}

			if ($data instanceof stdClass) {
				$data = (array) $data;
			}

			return (int) Arr::get($data, $period, 0);
		}, $this->period->keys());
	}
}
