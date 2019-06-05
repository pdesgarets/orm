<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;

class Test extends OrmFunctionalTestCase
{
    public function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(AbstractAttachment::class),
                    $this->em->getClassMetadata(AbstractComputedResult::class),
                    $this->em->getClassMetadata(ComputedAttachment::class),
                    $this->em->getClassMetadata(Checkup::class),
                    $this->em->getClassMetadata(A420Computed::class),
                ]
            );
        } catch (Exception $e) {
            dump($e);
        }
    }

    public function testIssue() : void
    {
        $checkup = new Checkup();
        $computedResult = new A420Computed();
        $attachment = new ComputedAttachment();
        $computedResult->attachment = $attachment;
        $attachment->computedResult = $computedResult;
        $checkup->computedResults->add($computedResult);


        $this->em->persist($checkup);
        $this->em->flush();
        self::assertCount(1, $this->em->getRepository(ComputedAttachment::class)->findAll());
        self::assertCount(1, $this->em->getRepository(A420Computed::class)->findAll());
        self::assertCount(1, $this->em->getRepository(Checkup::class)->findAll());

        $this->em->remove($checkup);
        $this->em->flush();

        self::assertEmpty($this->em->getRepository(ComputedAttachment::class)->findAll());
        self::assertEmpty($this->em->getRepository(A420Computed::class)->findAll());
        self::assertEmpty($this->em->getRepository(Checkup::class)->findAll());
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({"only"=ComputedAttachment::class})
 */
abstract class AbstractAttachment
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorMap({"computedResult"=A420Computed::class})
 */
abstract class AbstractComputedResult
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;
    /** @ORM\ManyToOne(targetEntity=Checkup::class, inversedBy="computedResults") */
    public $checkup;
}

/**
 * @ORM\Entity
 */
class ComputedAttachment extends AbstractAttachment
{
    /** @ORM\OneToOne(targetEntity=A420Computed::class, mappedBy="attachment") */
    public $computedResult;
}

/**
 * @ORM\Entity
 */
class ComputedAttachment2 extends AbstractAttachment
{
}

/**
 * @ORM\Entity
 */
class A420Computed extends AbstractComputedResult
{
    /** @ORM\OneToOne(targetEntity=ComputedAttachment::class, cascade={"persist", "remove"}, inversedBy="computedResult") */
    public $attachment;
}

/**
 * @ORM\Entity
 */
class Checkup
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;
    /** @ORM\OneToMany(targetEntity=AbstractComputedResult::class, cascade={"all"}, mappedBy="checkup") */
    public $computedResults;
    public function __construct() {
        $this->computedResults = new ArrayCollection();
    }

}

