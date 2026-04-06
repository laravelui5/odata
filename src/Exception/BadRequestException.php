<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Exception;

use Illuminate\Http\Response;
use LaravelUi5\OData\Protocol\Parser\ExpressionLexer;

class BadRequestException extends ProtocolException
{
    protected $httpCode = Response::HTTP_BAD_REQUEST;
    protected $odataCode = 'bad_request';
    protected $message = 'Bad request';

    public function lexer(ExpressionLexer $lexer): self
    {
        $this->addInnerError('lexer_error', $lexer->errorContext());

        return $this;
    }
}
