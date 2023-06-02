<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class GameController extends AbstractController
{
    private const BOARD_ROWS = 6;
    private const BOARD_COLUMNS = 7;

    #[Route('/game', name: 'app_game_index')]
    public function index(): Response
    {
        return $this->render('game/index.html.twig', [
            'controller_name' => 'GameController',
        ]);
    }

    #[Route('/game/new', name: 'app_game_new')]
    public function new(EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $game = new Game();
        $game->addPlayer($this->getUser());

        $logger->info('Une nouvelle partie est créer et à pour ID : {gameId}', [
            'gameId' => $game->getId(),
        ]);
        $logger->info('{namePlayer} a rejoint la partie.', [
            'namePlayer' => $game->getPlayers()->last(),
        ]);
        
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($game);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
        
        return $this->redirectToRoute('app_game_play', ['id' => $game->getId()]);
    }

    #[Route('/game/join/{id}', name: 'app_game_join')]
    public function gameSerialize(Game $game, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $game->addPlayer($this->getUser());

        $logger->info('{namePlayer} a rejoint la partie.', [
            'namePlayer' => $game->getPlayers()->last(),
        ]);
        
        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($game);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();

        return $this->redirectToRoute('app_game_play', ['id' => $game->getId()]);
    }

    #[Route('/game/{id}', name: 'app_game_play')]
    public function game(Game $game, EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        if(!$game->getPlayers()->contains($this->getUser())){
            return $this->redirectToRoute('app_home');
        }

        if($request->isMethod('POST')){
            $column = $request->get('column');
            $this->addTokenToColumn($game, $column, $entityManager);
        }

        $this->checkEndGame($game, $entityManager); 

        if($game->getLastMove() != null){
            $logger->info('{namePlayer} a joué.', [
                'namePlayer' => $game->getLastMove()->getUsername(),
            ]);
        }

        if($game->getWinner() != null){

            $logger->info('{namePlayer} a gagné la partie.', [
                'namePlayer' => $game->getWinner()->getUsername(),
            ]);

            if($game->getWinner() != $game->getPlayers()->first()){
                $this->sendEmailDefeat($mailer,$game->getPlayers()->first()); 
            }
            else{
                $this->sendEmailDefeat($mailer,$game->getPlayers()->last()); 
            }

           $this->sendEmailVictory($mailer,$game->getWinner()); 
   
            return $this->render('game/result.html.twig', [
                'result' => $game->getWinner()->getUserIdentifier(),
                'game' => $game,
            ]); 
        }

        return $this->render('game/game.html.twig', [
            'board' => $game->getGrid(),
            'game' => $game,
            'player' => $this->checkPlayer($game)->getUsername(),
        ]);
    }
    private function addTokenToColumn(Game $game, int $column, EntityManagerInterface $entityManager): void
    {
        $board = $game->getGrid();
        if($this->getUser() == $game->getPlayers()->first()){
            $token = 'red';
        }
        else{
            $token = 'yellow';
        }
        for ($i = 6 - 1; $i >= 0; --$i) {
            if ('' === $board[$i][$column]) {
                $board[$i][$column] = $token;
                break;
            }
        }

        $game->setGrid($board);
        $game->setLastMove($this->getUser());

        // tell Doctrine you want to (eventually) save the Product (no queries yet)
        $entityManager->persist($game);

        // actually executes the queries (i.e. the INSERT query)
        $entityManager->flush();
    }

    private function checkEndGame(Game $game, EntityManagerInterface $entityManager): void
    {
        $board = $game->getGrid();
        if($this->getUser() == $game->getPlayers()->first()){
            $player = 'red';
        }
        else{
            $player = 'yellow';
        }
        // Check horizontal
        for ($i = 0; $i < self::BOARD_ROWS; ++$i) {
            $count = 0;
            for ($j = 0; $j < self::BOARD_COLUMNS; ++$j) {
                if ($board[$i][$j] === $player) {
                    ++$count;
                    if (4 === $count) {
                        $game->setWinner($this->getUser());
                        $entityManager->persist($game);
                        $entityManager->flush();
                    }
                } else {
                    $count = 0;
                }
            }
        }

        // Check vertical
        for ($j = 0; $j < self::BOARD_COLUMNS; ++$j) {
            $count = 0;
            for ($i = 0; $i < self::BOARD_ROWS; ++$i) {
                if ($board[$i][$j] === $player) {
                    ++$count;
                    if (4 === $count) {
                        $game->setWinner($this->getUser());
                        $entityManager->persist($game);
                        $entityManager->flush();
                    }
                } else {
                    $count = 0;
                }
            }
        }

        // Check diagonal (top left to bottom right)
        for ($i = 0; $i <= self::BOARD_ROWS - 4; ++$i) {
            for ($j = 0; $j <= self::BOARD_COLUMNS - 4; ++$j) {
                $count = 0;
                for ($k = 0; $k < 4; ++$k) {
                    if ($board[$i + $k][$j + $k] === $player) {
                        ++$count;
                        if (4 === $count) {
                            $game->setWinner($this->getUser());
                            $entityManager->persist($game);
                            $entityManager->flush();
                        }
                    } else {
                        $count = 0;
                    }
                }
            }
        }

        // Check diagonal (bottom left to top right)
        for ($i = self::BOARD_ROWS - 1; $i >= 3; --$i) {
            for ($j = 0; $j <= self::BOARD_COLUMNS - 4; ++$j) {
                $count = 0;
                for ($k = 0; $k < 4; ++$k) {
                    if ($board[$i - $k][$j + $k] === $player) {
                        ++$count;
                        if (4 === $count) {
                            $game->setWinner($this->getUser());
                            $entityManager->persist($game);
                            $entityManager->flush();
                        }
                    } else {
                        $count = 0;
                    }
                }
            }
        }

    }

    private function checkPlayer(Game $game, UserRepository $user = null): User
    {
        if($game->getLastMove() == null){
            return $game->getPlayers()->first();
        }
        
        if($game->getLastMove() == $game->getPlayers()->first()){
            return $game->getPlayers()->last();
        }

        return $game->getPlayers()->first();
    }

    public function sendEmailVictory(MailerInterface $mailer, User $user)
    {
        $email = (new TemplatedEmail())
            ->from($user->getEmail())
            ->to('m2@symfony.com')
            ->subject('Victoire !')
            ->htmlTemplate('email/victory.html.twig');
        
;

        $mailer->send($email);
    }

    public function sendEmailDefeat(MailerInterface $mailer, User $user)
    {
        $email = (new TemplatedEmail())
            ->from($user->getEmail())
            ->to('m2@symfony.com')
            ->subject('Défaite !')
            ->htmlTemplate('email/defeat.html.twig');

        $mailer->send($email);

    }
}
