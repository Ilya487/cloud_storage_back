<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\RememberMeToken;
use App\Db\BaseRepository;

class RememberMeTokenRepository extends BaseRepository
{
    protected string $tableName = 'auth_tokens';

    public function getBySelector(string $selector): RememberMeToken|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('selector', $selector))
            ->build();

        $data = $this->getOne(whereClauseQuery: $query);

        if ($data !== false) {
            return RememberMeToken::createFromArr($data);
        }
        return $data;
    }

    public function saveToken(RememberMeToken $token): string
    {
        return $this->insert([
            'selector' => $token->selector,
            'validator_hash' => $token->validatorHash,
            'user_id' => $token->userId,
            'expires' => $token->getExpiresTimeInTimestampFormat()
        ]);
    }

    public function deleteBySelector(string $selector): void
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('selector', $selector))
            ->build();

        $this->delete($query);
    }
}
