<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Vocabularies\Common\V1\Label;
use LaravelUi5\OData\Vocabularies\Core\V1\Description;
use LaravelUi5\OData\Vocabularies\Ui\V1\Hidden;
use LaravelUi5\OData\Vocabularies\Ui\V1\LineItem;
use LaravelUi5\OData\Vocabularies\Ui\V1\SelectionFields;

/**
 * Test fixture: an Airport model annotated with OData vocabulary attributes.
 *
 * Uses PHP 8.4 property hooks to combine Eloquent attribute access with
 * vocabulary annotations on real PHP properties. Unannotated columns
 * continue through __get() as usual.
 */
#[Description('An airport with IATA code')]
#[SelectionFields(['code', 'name'])]
#[LineItem(['code', 'name'])]
class AnnotatedAirport extends Model
{
    protected $table = 'airports';
    public $timestamps = false;
    protected $guarded = [];

    #[Label('IATA Code')]
    #[Description('The IATA airport code')]
    public string $code {
        get => $this->getAttribute('code');
        set(string $value) => $this->setAttribute('code', $value);
    }

    #[Label('Airport Name')]
    public string $name {
        get => $this->getAttribute('name');
        set(string $value) => $this->setAttribute('name', $value);
    }

    #[Hidden]
    public ?int $country_id {
        get => $this->getAttribute('country_id');
        set(?int $value) => $this->setAttribute('country_id', $value);
    }
}
