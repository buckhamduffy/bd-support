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

	public function __construct(
		DateTimeInterface|string $from,
		DateTimeInterface|string $to,
		private ?PeriodInterval $interval = null,
		private int $intervalCount = 1
	)
	{
		$this->from = CarbonImmutable::parse($from)->startOfDay();
		$this->to = CarbonImmutable::parse($to)->endOfDay();
	}

	public static function make(
		DateTimeInterface|string $from,
		DateTimeInterface|string $to,
		?PeriodInterval $interval = null,
		int $intervalCount = 1
	): self
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
			$diffInDays < 14  => PeriodInterval::DAY,
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
			PeriodInterval::DAY   => 'days',
			PeriodInterval::WEEK  => 'weeks',
			PeriodInterval::MONTH => 'months',
			PeriodInterval::YEAR  => 'years',
		};

		$period = $period->{$interval}($this->intervalCount);

		return $this->period = $period->until($this->getTo());
	}

	/**
	 * @return Generator<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
	 */
	public function iterate(): Generator
	{
		foreach ($this->getPeriod()->getIterator() as $period) {
			$from = CarbonImmutable::parse($period);

			$to = match ($this->getInterval()) {
				PeriodInterval::DAY   => $from->addDays($this->intervalCount),
				PeriodInterval::WEEK  => $from->addWeeks($this->intervalCount),
				PeriodInterval::MONTH => $from->addMonths($this->intervalCount),
				PeriodInterval::YEAR  => $from->addYears($this->intervalCount),
			};

			$to = $to->isAfter($this->getTo()) ? $this->getTo() : $to->subSecond();

			yield [$from, $to];
		}
	}

	/**
	 * @return array<int, array{0: CarbonImmutable, 1: CarbonImmutable}>
	 */
	public function toArray(): array
	{
		return iterator_to_array($this->iterate());
	}

	/**
	 * @return Generator<int, string>
	 */
	public function iterateDisplay(): Generator
	{
		foreach ($this->iterate() as [$from, $to]) {
			yield match ($this->getInterval()) {
				PeriodInterval::DAY   => $from->format('Y-m-d'),
				PeriodInterval::WEEK  => $from->format('Y-m-d') . ' to ' . $to->format('Y-m-d'),
				PeriodInterval::MONTH => $from->format('M'),
				PeriodInterval::YEAR  => $from->format('Y'),
			};
		}
	}

	/**
	 * @return string[]
	 */
	public function toDisplayArray(): array
	{
		return iterator_to_array($this->iterateDisplay());
	}

	/**
	 * @return array{0: string, 1: string}
	 */
	public function toDb(bool $includeTime = false): array
	{
		if ($includeTime) {
			return [$this->getFrom()->toDateTimeString(), $this->getTo()->toDateTimeString()];
		}

		return [$this->getFrom()->toDateString(), $this->getTo()->toDateString()];
	}
}
