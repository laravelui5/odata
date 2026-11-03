<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataAnnotate;
use LaravelUi5\OData\Vocabularies\Common\V1\Label;
use LaravelUi5\OData\Vocabularies\Core\V1\Description;
use LaravelUi5\OData\Vocabularies\Ui\V1\CriticalityType;
use LaravelUi5\OData\Vocabularies\Ui\V1\DataField;
use LaravelUi5\OData\Vocabularies\Ui\V1\Hidden;
use LaravelUi5\OData\Vocabularies\Ui\V1\LineItem;
use LaravelUi5\OData\Vocabularies\Ui\V1\SelectionFields;

/**
 * Test fixture: an Airport model annotated with OData vocabulary attributes, all on the class.
 *
 * Type-level terms sit on the class directly; a column's terms go through #[ODataAnnotate], which names the
 * column. The model declares no PHP property, so Eloquent's attribute bag stays untouched.
 */
#[Description('An airport with IATA code')]
#[SelectionFields(['code', 'name'])]
#[LineItem([
    new DataField(value: new Path('code'), label: 'Code'),
    new DataField(value: new Path('name'), criticality: CriticalityType::Positive),
])]
#[ODataAnnotate('code', new Label('IATA Code'), new Description('The IATA airport code'))]
#[ODataAnnotate('name', new Label('Airport Name'))]
#[ODataAnnotate('country_id', new Hidden())]
class AnnotatedAirport extends Model
{
    protected $table = 'airports';
    public $timestamps = false;
    protected $guarded = [];
}
