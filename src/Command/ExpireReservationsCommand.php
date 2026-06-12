<?php

namespace App\Command;

use App\Service\ReservationExpiryService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:expire-reservations', description: 'Expire stale pending/confirmed reservations')]
final class ExpireReservationsCommand extends Command
{
    public function __construct(private readonly ReservationExpiryService $expiryService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = $this->expiryService->expireStaleReservations();
        $output->writeln(sprintf('Expired %d reservation(s).', $count));

        return Command::SUCCESS;
    }
}
