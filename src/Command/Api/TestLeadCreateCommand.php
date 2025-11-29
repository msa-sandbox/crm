<?php

declare(strict_types=1);

namespace App\Command\Api;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Handler\Lead\CreateLeadHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'api:test:leads:create',
    description: 'Test command to create leads (POST /leads)',
)]
class TestLeadCreateCommand extends Command
{
    use CliAuthTrait;

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CreateLeadHandler $handler,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('sample', null, InputOption::VALUE_NONE, 'Use built-in example JSON payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Authorize CLI user
        $this->authenticateCli($this->tokenStorage);

        // Prepare JSON payload (always use built-in example)
        $json = $this->sampleJson();

        // Deserialize JSON -> DTO collection
        $dto = $this->serializer->deserialize($json, CreateLeadCollectionDto::class, 'json');

        // Validate DTO
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $io->error('DTO validation failed');
            foreach ($violations as $violation) {
                $io->writeln(sprintf(
                    '- %s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                ));
            }

            return Command::FAILURE;
        }

        // Call handler (same logic as controller POST /leads)
        $result = $this->handler->createBulk($dto);

        // Show response data
        dump($result);

        $io->success('Leads created successfully');
        return Command::SUCCESS;
    }

    /**
     * Example JSON payload for lead creation.
     * Matches format expected by CreateLeadCollectionDto.
     */
    private function sampleJson(): string
    {
        return <<<JSON
[
  {
    "title": "Website redesign project",
    "status": "active",
    "pipelineStage": "proposal",
    "budget": 5000,
    "description": "Lead interested in redesigning corporate website.",
    "notes": "Has existing contract with competitor, may switch next month."
  },
  {
    "title": "Mobile app for e-commerce",
    "status": "active",
    "pipelineStage": "negotiation",
    "budget": 15000.50,
    "description": "Potential high-value customer for mobile app.",
    "notes": "Mentioned integration with CRM as requirement."
  }
]
JSON;
    }
}
