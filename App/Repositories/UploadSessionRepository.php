<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\BaseRepository;
use App\Tools\DbConnect;
use PDO;

class UploadSessionRepository  extends BaseRepository
{
    protected string $tableName = 'upload_sessions';

    public function __construct(
        DbConnect $dbConnect,
        private UserRepository $userRepo
    ) {
        parent::__construct($dbConnect);
    }

    public function createUploadSession(int $userId, string $fileName, int $totalChunks, ?string $destinationDirPath, int $fileSize): UploadSession|false
    {
        $this->beginTransaction();
        $canInsert = $this->userRepo->reserveDiskSpace($userId, $fileSize);
        if (!$canInsert) {
            $this->rollBackTransaction();
            return false;
        }

        $query = $this->queryBuilder->insert(['user_id', 'filename', 'destination_dir_path', 'total_chunks', 'file_size'])->build();
        $id =  $this->insert($query, [
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_path' => $destinationDirPath,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize
        ]);

        $this->submitTransaction();

        return UploadSession::createFromArr([
            'id' => $id,
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_path' => $destinationDirPath,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize,
            'completed_chunks' => 0,
            'status' => UploadSessionStatus::UPLOADING->value
        ]);
    }

    public function deleteSession(UploadSession $session)
    {
        $this->beginTransaction();
        $this->userRepo->freeUpDiskSpace($session->userId, $session->flieSize);
        $query = $this->queryBuilder
            ->delete()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $this->delete($query, ['user_id' => $session->userId, 'id' => $session->id]);
        $this->submitTransaction();
    }

    public function getById(int $userId, int $sessionId): UploadSession|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $data = $this->fetchOne($query, ['user_id' => $userId, 'id' => $sessionId]);
        return $data == false ? false : UploadSession::createFromArr($data);
    }

    public function incrementCompletedChunks(int $uploadSessionId): int|false
    {
        $updateQuery = $this->queryBuilder->incrementField('completed_chunks')->where(Expression::equal('id'))->build();
        $selectQuery = $this->queryBuilder->select(['completed_chunks'])->where(Expression::equal('id'))->build();

        $this->beginTransaction();
        $this->update($updateQuery, ['id' => $uploadSessionId]);
        $count = $this->fetchOne($selectQuery, ['id' => $uploadSessionId])['completed_chunks'];
        $this->submitTransaction();

        return $count;
    }

    public function isNameExist(int $userId, string $fileName, ?string $destinationDirPath)
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('filename'));

        if (is_null($destinationDirPath)) {
            $query = $query->and(Expression::isNull('destination_dir_path'))->build();
            $params = ['user_id' => $userId, 'filename' => $fileName];
        } else {
            $query = $query->and(Expression::like('destination_dir_path', 'pathPattern'))->build();
            $params = ['user_id' => $userId, 'filename' => $fileName, 'pathPattern' => $destinationDirPath];
        }
        $query .= ' AND (status=\'' . UploadSessionStatus::UPLOADING->value . '\' OR status=\'' . UploadSessionStatus::BUILDING->value . '\')';
        return $this->fetchOne($query, $params, PDO::FETCH_NUM)[0] != 0;
    }

    public function getUserSessionsCount(int $userId): int
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('status'))
            ->build();
        return $this->fetchOne($query, ['user_id' => $userId, 'status' => UploadSessionStatus::UPLOADING->value], PDO::FETCH_NUM)[0];
    }

    /**
     * @return UploadSession[]
     */
    public function getUserSessions(int $userId): array
    {
        $query = $this->queryBuilder->select()->where(Expression::equal('user_id'))->build();
        $data = $this->fetchAll($query, ['user_id' => $userId]);
        $res = [];
        foreach ($data as $session) {
            $res[] = UploadSession::createFromArr($session);
        }
        return $res;
    }

    public function setStatus(int $userId, int $sessionId, UploadSessionStatus $status)
    {
        $query = $this->queryBuilder
            ->update(['status'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();

        $this->update($query, ['user_id' => $userId, 'id' => $sessionId, 'status' => $status->value]);
    }
}
