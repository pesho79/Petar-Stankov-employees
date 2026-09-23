<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\InvalidCsvException;
use App\Service\Csv\EmployeeCsvReader;
use App\Service\EmployeeCollaborationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmployeeController extends AbstractController
{
    #[Route('/', name: 'employee_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EmployeeCsvReader $employeeCsvReader,
        EmployeeCollaborationService $employeeCollaborationService,
    ): Response {
        $results = [];
        $error = null;

        if ($request->isMethod('POST')) {
            $file = $request->files->get('file');

            if (!$file instanceof UploadedFile) {
                $error = 'Please select a CSV file.';
            } elseif (!$file->isValid()) {
                $error = sprintf(
                    'The uploaded file could not be processed. Error code: %d.',
                    $file->getError()
                );
            } else {
                try {
                    $records = $employeeCsvReader->read(
                        $file->getPathname()
                    );

                    $results = $employeeCollaborationService->calculate(
                        $records
                    );
                } catch (InvalidCsvException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        return $this->render('employee/index.html.twig', [
            'results' => $results,
            'error' => $error,
        ]);
    }
}
