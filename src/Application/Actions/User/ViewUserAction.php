<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;

class ViewUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $idArg = $this->resolveArg('id');
        if (!is_numeric($idArg)) {
            $this->logger->warning('Invalid user id argument for ViewUserAction', ['id' => $idArg]);
            return $this->respondWithData(['error' => 'Invalid user id'], 400);
        }
        $userId = (int) $idArg;
        $user = $this->userRepository->findUserOfId($userId);

        $this->logger->info("User of id `${userId}` was viewed.");

        return $this->respondWithData($user);
    }
}
