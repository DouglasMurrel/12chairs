<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Nelexa\Zip\ZipFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Service\OrderList;

class MainController extends AbstractController
{
    #[Route(path: '/', name: 'main')]
    public function main(Request $request): Response
    {
        return new Response('hello world');
    }
    
    #[Route('/download/orders/', name: 'download_protected_zip')]
    public function downloadProtectedZip(OrderList $orderList): Response
    {
        // 1. Генерируем текст (здесь можно сделать любую логику)
        $text = $orderList->generateText();
        
        // 2. Создаем запароленный ZIP
        $zipPassword = 'MasteraKozly'; // Пароль для архива (можно генерировать динамически)
        
        // Создаём временный файл для ZIP (не храним в памяти)
        $tempFile = tempnam(sys_get_temp_dir(), 'protected_zip_');
        
        try {
            $zip = new ZipFile();
            $zip->setComment('Защищенный архив');
            
            // Добавляем файл с текстом в архив (без сохранения на диск)
            $zip->addFromString('document.txt', $text);
            
            // Устанавливаем пароль для всех файлов в архиве
            $zip->setPassword($zipPassword);
            
            // Сохраняем архив во временный файл
            $zip->saveAsFile($tempFile);
            $zip->close();
            
            // 3. Отдаём архив пользователю
            $response = new StreamedResponse(function () use ($tempFile) {
                $stream = fopen($tempFile, 'rb');
                fpassthru($stream);
                fclose($stream);
                unlink($tempFile); // Удаляем временный файл после отправки
            });
            
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'protected_archive.zip'
            ));
            $response->headers->set('Content-Length', filesize($tempFile));
            
            return $response;
            
        } catch (\Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $this->createNotFoundException('Ошибка создания архива: ' . $e->getMessage());
        }
    }
}
