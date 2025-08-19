<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\FoodServiceInterface;
use App\Request\FoodAddRequestInputType;
use App\Request\FoodQueryRequestType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

/**
 * API controller for food operations.
 */
class FoodController extends AbstractController
{
    /**
     * @param FoodServiceInterface $foodService
     */
    public function __construct(private FoodServiceInterface $foodService)
    {
    }

    /**
     * GET /api/food?type=&q=&unit=
     * List/search items with optional unit conversion in the response.
     */
    #[Route('/api/food', name: 'food_search', methods: ['GET'])]
    public function search(#[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] FoodQueryRequestType $query): JsonResponse
    {

        $items = array_map(
            fn($f) => $this->foodService->format($f, $query->unit),
            $this->foodService->query($query->type, $query->q),
        );

        return new JsonResponse($items, Response::HTTP_OK);
    }

    /**
     * POST /api/food
     * Create a new item.
     */
    #[Route('/api/food', name: 'food_add', methods: ['POST'])]
    public function add(#[MapRequestPayload] FoodAddRequestInputType $input): JsonResponse
    {
        $food = $this->foodService->add(
            $input->name,
            $input->type,
            $input->quantity,
            $input->unit
        );
        return new JsonResponse($this->foodService->format($food, 'g'), Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/food/{id}
     * Remove an item by ID.
     */
    #[Route('/api/food/{id}', name: 'food_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->foodService->remove($id);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
