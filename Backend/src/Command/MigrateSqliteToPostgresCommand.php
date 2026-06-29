<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-sqlite',
    description: 'Importe les données de l\'ancienne base SQLite (var/data.db) vers PostgreSQL (ecom)',
)]
class MigrateSqliteToPostgresCommand extends Command
{
    /** @var list<string> */
    private const TABLES_ORDRE = [
        'utilisateur',
        'categorie',
        'bon_reduction',
        'produit',
        'image_produit',
        'adresse',
        'commande',
        'ligne_commande',
        'avis',
        'element_liste_souhaits',
    ];

  /** @var array<string, list<string>> */
    private const BOOL_COLONNES = [
        'produit' => ['actif'],
        'adresse' => ['par_defaut'],
        'bon_reduction' => ['actif'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('sqlite', null, InputOption::VALUE_OPTIONAL, 'Chemin vers data.db', 'var/data.db')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Écraser les données PostgreSQL existantes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sqlitePath = $this->projectDir . '/' . ltrim((string) $input->getOption('sqlite'), '/');

        if (!is_file($sqlitePath)) {
            $io->error("Fichier SQLite introuvable : $sqlitePath");

            return Command::FAILURE;
        }

        $sqlite = new \PDO('sqlite:' . $sqlitePath);
        $sqlite->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pg = $this->em->getConnection();

        if (!$input->getOption('force')) {
            $io->warning('Cette opération remplace toutes les données de PostgreSQL par celles de SQLite.');
            if (!$io->confirm('Continuer ?', false)) {
                return Command::SUCCESS;
            }
        }

        $io->title('Migration SQLite → PostgreSQL (ecom)');

        $pg->executeStatement('SET session_replication_role = replica');

        try {
            $pg->executeStatement(
                'TRUNCATE TABLE '
                . implode(', ', self::TABLES_ORDRE)
                . ' RESTART IDENTITY CASCADE'
            );

            foreach (self::TABLES_ORDRE as $table) {
                $rows = $sqlite->query("SELECT * FROM \"$table\"")->fetchAll(\PDO::FETCH_ASSOC);
                if ($rows === []) {
                    $io->writeln("  <comment>$table</comment> : 0 ligne");
                    continue;
                }

                foreach ($rows as $row) {
                    $this->insererLigne($pg, $table, $row);
                }

                $this->reinitialiserSequence($pg, $table);
                $io->writeln("  <info>$table</info> : " . count($rows) . ' ligne(s)');
            }
        } finally {
            $pg->executeStatement('SET session_replication_role = DEFAULT');
        }

        $io->success('Migration terminée. Les données SQLite sont maintenant dans PostgreSQL.');

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $row */
    private function insererLigne(Connection $pg, string $table, array $row): void
    {
        foreach (self::BOOL_COLONNES[$table] ?? [] as $colonne) {
            if (array_key_exists($colonne, $row)) {
                $row[$colonne] = (bool) $row[$colonne];
            }
        }

        if ($table === 'utilisateur' && isset($row['roles']) && is_string($row['roles'])) {
            $row['roles'] = $row['roles'];
        }

        $colonnes = array_keys($row);
        $placeholders = array_map(static fn (string $c) => ':' . $c, $colonnes);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $colonnes),
            implode(', ', $placeholders),
        );

        $pg->executeStatement($sql, $row);
    }

    private function reinitialiserSequence(Connection $pg, string $table): void
    {
        $seq = $pg->fetchOne("SELECT pg_get_serial_sequence('$table', 'id')");
        if (!$seq) {
            return;
        }

        $max = (int) $pg->fetchOne("SELECT COALESCE(MAX(id), 0) FROM $table");
        if ($max > 0) {
            $pg->executeStatement("SELECT setval('$seq', $max, true)");
        }
    }
}
