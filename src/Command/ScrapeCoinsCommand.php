<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Coin;
use App\Repository\CoinRepository;
use App\Service\LbLtCoinScraper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:scrape-coins',
    description: 'Scrape Lithuanian collector and commemorative coins from lb.lt',
)]
final class ScrapeCoinsCommand extends Command
{
    public function __construct(
        private readonly LbLtCoinScraper $scraper,
        private readonly CoinRepository $coinRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            'l',
            InputOption::VALUE_REQUIRED,
            'Limit the number of coins to scrape (useful for testing)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Scraping coins from lb.lt');

        $limitOption = $input->getOption('limit');
        $limit = is_string($limitOption) ? (int) $limitOption : null;

        $io->section('Step 1: Fetching coin listings...');
        $listings = $this->scraper->scrapeListings();
        $io->info(sprintf('Found %d coins in listings.', count($listings)));

        if ($limit !== null) {
            $listings = array_slice($listings, 0, $limit);
            $io->info(sprintf('Limited to %d coins.', count($listings)));
        }

        $io->section('Step 2: Scraping coin details...');
        $io->progressStart(count($listings));

        $created = 0;
        $skipped = 0;

        foreach ($listings as $listing) {
            $io->progressAdvance();

            $externalId = $listing['url'];

            $existing = $this->coinRepository->findOneBy(['externalId' => $externalId]);
            if ($existing !== null) {
                $skipped++;
                continue;
            }

            try {
                $detail = $this->scraper->scrapeDetail($listing['url']);

                $coin = new Coin();
                $coin->setName($listing['name']);
                $coin->setExternalId($externalId);
                $coin->setDescription($detail['description']);
                $coin->setDenomination($detail['denomination']);
                $coin->setMetal($detail['metal']);
                $coin->setDiameterMm($detail['diameterMm']);
                $coin->setWeightGrams($detail['weightGrams']);
                $coin->setMintage($detail['mintage']);
                $coin->setYear($detail['year']);
                $coin->setImageUrl($detail['imageUrl'] ?? $listing['imageUrl']);

                $this->entityManager->persist($coin);
                $created++;

                // Flush every 20 coins to avoid memory issues
                if ($created % 20 === 0) {
                    $this->entityManager->flush();
                }

                // Be polite to the server
                usleep(200_000);
            } catch (\Throwable $e) {
                $io->warning(sprintf('Failed to scrape %s: %s', $listing['name'], $e->getMessage()));
            }
        }

        $this->entityManager->flush();
        $io->progressFinish();

        $io->success(sprintf('Done! Created: %d, Skipped (already exist): %d', $created, $skipped));

        return Command::SUCCESS;
    }
}
