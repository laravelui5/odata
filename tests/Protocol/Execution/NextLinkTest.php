<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP05: a next link stands for the rest of the same collection query. It used to
 * be `<Set>?$skip=n` — no $filter, $orderby, $select, $count or custom options,
 * and for a navigation collection the whole target set.
 */

uses(TestCase::class);

beforeEach(function () {
    $this->withExceptionHandling();
    $this->app->instance(ODataServiceRegistryInterface::class, new FlightServiceRegistry());

    foreach (range(1, 10) as $i) {
        DB::table('flights')->insert([
            'id'          => $i,
            'origin'      => $i % 2 === 0 ? 'lhr' : 'lax',
            'destination' => sprintf('d%02d', $i),
        ]);
    }
    foreach (range(1, 5) as $i) {
        DB::table('passengers')->insert(['id' => $i, 'flight_id' => 1, 'name' => "p{$i}"]);
        DB::table('passengers')->insert(['id' => $i + 10, 'flight_id' => 2, 'name' => "q{$i}"]);
    }
});

/** Follow next links to the end; returns every page's decoded body. */
function followPages(object $test, string $url, array $headers = []): array
{
    $pages = [];
    while ($url !== null && count($pages) < 20) {
        $page    = json_decode($test->get($url, $headers)->streamedContent(), true);
        $pages[] = $page;
        $url     = $page['@odata.nextLink'] ?? null;
    }
    return $pages;
}

it('pages a filtered, sorted, projected collection without losing the query', function () {
    $pages = followPages(
        $this,
        "/odata/Flights?\$filter=origin eq 'lhr'&\$orderby=destination desc&\$select=destination,origin&\$count=true&client=probe",
        ['Prefer' => 'odata.maxpagesize=2'],
    );

    $rows = array_merge(...array_column($pages, 'value'));

    expect(count($pages))->toBe(3)
        ->and(array_column($rows, 'destination'))->toBe(['d10', 'd08', 'd06', 'd04', 'd02'])
        ->and(array_unique(array_column($rows, 'origin')))->toBe(['lhr'])
        ->and(array_keys($rows[0]))->toBe(['destination', 'origin'])
        ->and(array_column($pages, '@odata.count'))->toBe([5, 5, 5])
        ->and($pages[0]['@odata.nextLink'])->toContain('client=probe')
        ->and($pages[0]['@odata.nextLink'])->toEndWith('$skip=2');
});

describe('RequestTarget::nextLink()', function () {
    $root = 'http://localhost/odata/';

    it('keeps the path of a navigation collection', function () use ($root) {
        expect((new LaravelUi5\OData\Protocol\Execution\RequestTarget('/Flights(1)/passengers'))->nextLink($root, 2))
            ->toBe('http://localhost/odata/Flights(1)/passengers?$skip=2');
    });

    it('carries every other parameter byte for byte and replaces $skip', function () use ($root) {
        $target = new LaravelUi5\OData\Protocol\Execution\RequestTarget(
            '/Flights',
            "%24filter=origin%20eq%20%27lhr%27&\$skip=4&\$orderby=destination&sap-client=100",
        );

        expect($target->nextLink($root, 6))
            ->toBe("http://localhost/odata/Flights?%24filter=origin%20eq%20%27lhr%27&\$orderby=destination&sap-client=100&\$skip=6");
    });

    it('recognises an encoded $skip as $skip', function () use ($root) {
        expect((new LaravelUi5\OData\Protocol\Execution\RequestTarget('/Flights', '%24skip=2&%24top=10'))->nextLink($root, 4))
            ->toBe('http://localhost/odata/Flights?%24top=10&$skip=4');
    });
});
