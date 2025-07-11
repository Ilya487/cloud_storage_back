<?php

namespace App\Repositories;

use App\Models\RememberMeToken;
use App\Repositories\BaseRepository;

class RememberMeTokenRepository extends BaseRepository
{
    public function getBySelector(string $selector): RememberMeToken|false
    {
        $query = $this->queryBuilder->select()->where('selector', '=')->build();
        $data = $this->fetchOne($query, ['selector' => $selector]);

        if ($data !== false) {
            return RememberMeToken::createFromArr($data);
        }
        return $data;
    }

    public function saveToken(RememberMeToken $token): string
    {
        $query = $this->queryBuilder->insert(['selector', 'validator_hash', 'user_id', 'expires'])->build();
        return $this->insert($query, [
            'selector' => $token->selector,
            'validator_hash' => $token->validatorHash,
            'user_id' => $token->userId,
            'expires' => $token->getExpiresTimeInTimestampFormat()
        ]);
    }

    public function deleteBySelector(string $selector): void
    {
        $query = $this->queryBuilder->delete()->where('selector', '=')->build();
        $this->delete($query, ['selector' => $selector]);
    }
}
