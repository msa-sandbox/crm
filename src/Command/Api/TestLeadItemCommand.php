<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Lead\GetLeadItemQueryDto;
use App\Api\V1\Handler\Lead\GetLeadHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:leads:item',
    description: 'Test command to get one lead (GET /leads/{id})',
)]
class TestLeadItemCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly GetLeadHandler $handler,
        private readonly ValidatorInterface $validator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Lead ID')
            ->addOption('with', null, InputOption::VALUE_OPTIONAL, 'Related entities, e.g. "contacts"')
            ->addOption('include-deleted', null, InputOption::VALUE_NONE, 'Include soft-deleted lead');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // CLI authentication
        $this->authenticateCli($this->tokenStorage);

        $id = (int) $input->getArgument('id');
        $with = $input->getOption('with');
        $includeDeleted = $input->getOption('include-deleted');

        // Build DTO
        $dto = new GetLeadItemQueryDto(
            with: $with,
            includeDeleted: $includeDeleted,
        );

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $io->error('DTO validation failed');
            foreach ($violations as $violation) {
                $io->writeln(sprintf('- %s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }

            return Command::FAILURE;
        }

        $result = $this->handler->getOneById($id, $dto);

        dump($result);

        $io->success('Done');

        return Command::SUCCESS;
    }
}
