<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:backup',
    description: 'Sauvegarde la base de données et le dossier uploads.'
)]
class BackupCommand extends Command
{
    public function __construct() 
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = (new \DateTimeImmutable())->format('Y-m-d_H-i-s');

        $backupDir = dirname(__DIR__, 2).'/var/backups';
        $databaseDir = $backupDir.'/database';
        $uploadsDir = $backupDir.'/uploads';

        foreach ([$backupDir, $databaseDir, $uploadsDir] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }

        $output->writeln('📦 Sauvegarde RH Digital Studio');
        $output->writeln('Dossier : '.$backupDir);

        $databaseName = 'rhdigitalstudio';
        $databaseUser = 'root';
        $databasePassword = '';

        $sqlFile = $databaseDir.'/database_'.$date.'.sql';

        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';

        $command = sprintf(
            '"%s" -u%s %s %s > "%s"',
            $mysqldump,
            escapeshellarg($databaseUser),
            $databasePassword ? '-p'.escapeshellarg($databasePassword) : '',
            escapeshellarg($databaseName),
            $sqlFile
        );

        $output->writeln('🗄️ Sauvegarde de la base de données...');

        exec($command, $result, $exitCode);

        if ($exitCode !== 0) {
            $output->writeln('❌ Erreur pendant le dump SQL.');
            return Command::FAILURE;
        }

        $output->writeln('✅ Base sauvegardée : '.$sqlFile);
        $output->writeln('📁 Sauvegarde du dossier uploads...');

        $sourceUploads = dirname(__DIR__, 2).'/public/uploads';
        $zipFile = $uploadsDir.'/uploads_'.$date.'.zip';

        if (!is_dir($sourceUploads)) {
            $output->writeln('⚠️ Aucun dossier uploads trouvé.');
        } else {
            $zip = new \ZipArchive();

            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                $output->writeln('❌ Impossible de créer le ZIP uploads.');
                return Command::FAILURE;
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceUploads, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $filePath = $file->getRealPath();

                $relativePath = substr($filePath, strlen($sourceUploads) + 1);

                $zip->addFile($filePath, $relativePath);
            }

            $zip->close();

            $output->writeln('✅ Uploads sauvegardés : '.$zipFile);
        }
        $output->writeln('🧹 Nettoyage des anciennes sauvegardes...');

        $retentionDays = 30;
        $limitDate = (new \DateTimeImmutable())->modify('-'.$retentionDays.' days');

        $backupFiles = array_merge(
            glob($databaseDir.'/*.sql') ?: [],
            glob($uploadsDir.'/*.zip') ?: []
        );

        $deletedCount = 0;

        foreach ($backupFiles as $file) {
            $fileDate = (new \DateTimeImmutable())->setTimestamp(filemtime($file));

            if ($fileDate < $limitDate) {
                unlink($file);
                $deletedCount++;
            }
        }

        $output->writeln('✅ Nettoyage terminé : '.$deletedCount.' ancien(s) fichier(s) supprimé(s).');
        
        return Command::SUCCESS;
    }
}
