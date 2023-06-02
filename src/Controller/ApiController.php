<?php

namespace App\Controller;

use App\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class ApiController extends AbstractController
{

    #[Route('/api/game/{id}.{extension}', name: 'app_game_serialize')]
    public function gameSerialize(Game $game, String $extension): Response
    {
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new GetSetMethodNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        dump(json_encode($game->getPlayers()));
       
        return new Response($serializer->serialize($game, $extension));

    }

}
