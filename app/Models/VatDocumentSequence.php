<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The last number issued for one document series in one year. Read and advanced only under a row lock, inside the
 * transaction that creates the document (see ChallanNumberer).
 */
class VatDocumentSequence extends Model
{
    protected $guarded = [];
}
