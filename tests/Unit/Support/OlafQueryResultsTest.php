<?php

namespace Tests\Unit\Support;

use App\Support\Olaf\QueryResults;
use Tests\TestCase;

class OlafQueryResultsTest extends TestCase
{
	// NB: olaf --fragmented emits one CSV block (header included) per matched query window, all
	// for the same query file - this is a trimmed real sample with three windows, the middle one
	// being the best match
	private const RAW = <<<'RAW'
	query_index, total_queries, query_path, query_offset, match_count, query_start, query_stop, path, match_identifier, reference_start, reference_stop
	1 ,1 ,/root/audio/query.mp3, 0.000, 600 ,0.088 ,29.024, /root/audio/8-bit-takeover-367276.mp3, 2697779190, 0.088, 29.024
	query_index, total_queries, query_path, query_offset, match_count, query_start, query_stop, path, match_identifier, reference_start, reference_stop
	1 ,1 ,/root/audio/query.mp3, 0.000, 641 ,0.280 ,29.648, /root/audio/8-bit-takeover-367276.mp3, 2697779190, 30.280, 59.648
	query_index, total_queries, query_path, query_offset, match_count, query_start, query_stop, path, match_identifier, reference_start, reference_stop
	1 ,1 ,/root/audio/query.mp3, 0.000, 100 ,0.288 ,6.824, /root/audio/8-bit-takeover-367276.mp3, 2697779190, 120.288, 126.824
	RAW;

	// NB: query_path comes back as the absolute container path (/root/audio/query.mp3), so the
	// caller's relative $filename ('query.mp3') has to be matched against it after normalising -
	// otherwise every row is filtered out and no match is ever found
	public function testMatchesQueryPathAgainstRelativeFilename(): void
	{
		$results = new QueryResults('query.mp3', static::RAW);

		$this->assertCount(3, $results->items);
	}

	public function testBestMatchAcrossAllWindowsIsThePeakConfidence(): void
	{
		$results = new QueryResults('query.mp3', static::RAW);

		$best = $results->items->first();

		$this->assertEquals(641, $best['confidence']);
		$this->assertEquals('8-bit-takeover-367276.mp3', $best['file']);
	}

	public function testYieldsNoMatchesForADifferentQueryFile(): void
	{
		$results = new QueryResults('some-other-file.mp3', static::RAW);

		$this->assertCount(0, $results->items);
	}

	public function testZeroConfidenceRowsAreExcluded(): void
	{
		$raw = <<<'RAW'
		query_index, total_queries, query_path, query_offset, match_count, query_start, query_stop, path, match_identifier, reference_start, reference_stop
		1 ,1 ,/root/audio/query.mp3, 0.000, 0 ,0.088 ,29.024, /root/audio/8-bit-takeover-367276.mp3, 2697779190, 0.088, 29.024
		RAW;

		$results = new QueryResults('query.mp3', $raw);

		$this->assertCount(0, $results->items);
	}
}
