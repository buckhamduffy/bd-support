<?php

namespace BuckhamDuffy\BdSupport\Helpers;

use Generator;
use DateTimeInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use BuckhamDuffy\BdSupport\Enums\PeriodInterval;

class Period
{
	private ?CarbonPeriodImmutable $period = null;
	private CarbonImmutable $from;
	private CarbonImmutable $to;
	private int $padLength = 2;
	private array $indexes = [];
	private PeriodDb $dbHelper;

	public function __construct(
		DateTimeInterface|string $from,
		DateTimeInterface|string $to,
		private ?PeriodInterval $interval = null,
		private int $intervalCount = 1
	)
	{
		$this->from = CarbonImmutable::parse($from)->startOfDay();
		$this->to = CarbonImmutable::parse($to)->endOfDay();

		$totalPeriods = iterator_count($this->iterate());
		$this->padLength = \strlen((string) $totalPeriods);
	}

	public static function make(DateTimeInterface|string $from, DateTimeInterface|string $to, ?PeriodInterval $interval = null, int $intervalCount = 1): self
	{
		return new self($from, $to, $interval, $intervalCount);
	}

	public function getFrom(): CarbonImmutable
	{
		return $this->from;
	}

	public function getTo(): CarbonImmutable
	{
		return $this->to;
	}

	public function getInterval(): PeriodInterval
	{
		if ($this->interval) {
			return $this->interval;
		}

		$diffInDays = $this->getFrom()->diffInDays($this->getTo());

		return match (true) {
			$diffInDays > 365 => PeriodInterval::YEAR,
			$diffInDays > 60  => PeriodInterval::MONTH,
			$diffInDays > 14  => PeriodInterval::WEEK,
			default           => PeriodInterval::DAY,
		};
	}

	public function getPeriod(): CarbonPeriodImmutable
	{
		if ($this->period) {
			return $this->period;
		}

		$period = CarbonPeriodImmutable::start($this->getFrom());

		$interval = match ($this->getInterval()) {
			PeriodInterval::DAY  => 'days',
			PeriodInterval::WEEK => 'weeks',
			PeriodInterval::MONTH, PeriodInterval::QUARTER => 'months',
			PeriodInterval::YEAR => 'years',
		};

		// If the interval is quarters, multiply the interval count by 3 to get the equivalent in months
		$intervalCount = $this->getInterval() === PeriodInterval::QUARTER ? $this->intervalCount * 3 : $this->intervalCount;

		$period = $period->{$interval}($intervalCount);

		return $this->period = $period->until($this->getTo());
	}

	/**
	 * @return Generator<array-key, array{0: CarbonImmutable, 1: CarbonImmutable}>
	 */
	public function iterate(): Generator
	{
		foreach ($this->getPeriod()->getIterator() as $period) {
			$from = CarbonImmutable::parse($period);

			$to = match ($this->getInterval()) {
				PeriodInterval::DAY     => $from->addDays($this->intervalCount),
				PeriodInterval::WEEK    => $from->addWeeks($this->intervalCount),
				PeriodInterval::MONTH   => $from->addMonths($this->intervalCount),
				PeriodInterval::YEAR    => $from->addYears($this->intervalCount),
				PeriodInterval::QUARTER => $from->addQuarter(),
			};

			if ($to->isAfter($this->getTo())) {
				$to = $this->getTo();
			} else {
				$to = $to->subSecond();
			}

			yield [$from, $to];
		}
	}

	/**
	 * @return Generator<string>
	 */
	public function iterateFormatted(bool $humanReadable = false): Generator
	{
		foreach ($this->iterate() as [$from, $to]) {
			yield match ($this->getInterval()) {
				PeriodInterval::DAY     => $from->format($humanReadable ? 'd/m/Y' : 'Y-m-d'),
				PeriodInterval::WEEK    => $to->format($humanReadable ? 'd/m/Y' : 'Y-m-d'),
				PeriodInterval::MONTH   => $from->format($humanReadable ? 'M y' : 'Y-m-01'),
				PeriodInterval::YEAR    => $from->format('Y'),
				PeriodInterval::QUARTER => 'Q' . $from->quarter . ' ' . $from->format('y'),
			};
		}
	}

	/**
	 * @return int[]
	 */
	public function indexes(): array
	{
		if (\count($this->indexes)) {
			return $this->indexes;
		}

		foreach ($this->iterate() as $index => $value) {
			$this->indexes[] = $index;
		}

		return $this->indexes;
	}

	/**
	 * @return string[]
	 */
	public function keys(): array
	{
		return array_map(fn ($index) => $this->getPeriodIndex($index), $this->indexes());
	}

	public function toDb(): array
	{
		return $this->dbHelper()->toDb();
	}

	/**
	 * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
	 */
	public function toArray(): array
	{
		return iterator_to_array($this->iterate());
	}

	/**
	 * @return array<int, string>
	 */
	public function toList(string $format = 'Y-m-d'): array
	{
		return array_values(array_map(function(array $span) use ($format) {
			return $span[0]->format($format);
		}, $this->toArray()));
	}

	public function toCategories(): array
	{
		return iterator_to_array($this->iterateFormatted(true));
	}

	public function getPeriodIndex(int $index): string
	{
		return 'period_' . str_pad((string) $index, $this->padLength, '0', \STR_PAD_LEFT);
	}

	public function dbHelper(): PeriodDb
	{
		if (isset($this->dbHelper)) {
			return $this->dbHelper->setPeriod($this);
		}

		return $this->dbHelper = new PeriodDb($this);
	}
}
