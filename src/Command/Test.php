<?php

declare(strict_types=1);

namespace App\Command;

use App\Api\V1\Dto\Request\Lead\CreateLeadCollectionDto;
use App\Api\V1\Dto\Request\Lead\CreateLeadWithContactCollectionDto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'test',
    description: '',
)]
class Test extends Command
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //        $json = $this->lead();
        //        $dto = $this->serializer->deserialize($json, CreateLeadCollectionDto::class, 'json');

        $json = $this->leadWithContact();
        $dto = $this->serializer->deserialize($json, CreateLeadWithContactCollectionDto::class, 'json');

        // Валидация
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            dd($violations);
        }

        dd($dto);
    }

    public function lead(): string
    {
        $json = <<<JSON
[
  {
    "title": "Website redesign project",
    "status": "active",
    "pipelineStage": "proposal",
    "budget": "5000",
    "description": "Lead interested in redesigning corporate website.",
    "notes": "Has existing contract with competitor, may switch next month."
  },
  {
    "title": "Mobile app for e-commerce",
    "status": "active",
    "budget": 15000.01,
    "description": "Potential high-value customer for mobile app."
  }
]
JSON;

        return $json;
    }

    public function leadWithContact(): string
    {
        $json = <<<JSON
[
  {
    "title": "Website redesign project",
    "status": "active",
    "pipelineStage": "proposal",
    "budget": 5000,
    "description": "Lead interested in redesigning corporate website.",
    "notes": "Has existing contract with competitor.",
    "_embedded": {
      "contacts": [
        {
          "firstName": "Bob",
          "lastName": "Smith",
          "email": "bob.smith@example.com",
          "phone": "+442071838750",
          "company": "Techify Ltd",
          "city": "London",
          "country": "UK"
        },
        {
          "firstName": "Carol",
          "lastName": "Davis",
          "email": "carol.davis@example.org",
          "phone": "+33142345678",
          "company": "Techify Ltd",
          "city": "Paris",
          "country": "France"
        }
      ]
    }
  },
  {
    "title": "CRM integration consulting",
    "status": "active",
    "pipelineStage": "negotiation",
    "budget": 2500,
    "description": "Potential CRM consulting engagement.",
    "notes": "Requested follow-up next week.",
    "_embedded": {
      "contacts": [
        {
          "firstName": "Alice",
          "lastName": "Johnson",
          "email": "alice@example.com",
          "phone": "+12025550123",
          "company": "Acme Corp",
          "city": "New York",
          "country": "USA",
          "notes": "Primary contact for project"
        }
      ]
    }
  }
]
JSON;

        return $json;
    }
}
