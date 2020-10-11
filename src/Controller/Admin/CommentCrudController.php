<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;


use App\Entity\Comment;


class CommentCrudController extends AbstractCrudController
{

    private $photoBase;

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function __construct(string $photoBase)
    {
        $this->photoBase = $photoBase;
    }
   
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('author'),
            TextareaField::new('text'),
            TextField::new('email'),
            DateTimeField::new('createdAt')->hideOnForm(),
            ImageField::new('photoFilename')->setBasePath($this->photoBase),
            AssociationField::new('conference'),
            ChoiceField::new('state')->setChoices(
                ['Submitted' => 'submitted', 
                'Ham' => 'ham',
                'Potential spam' => 'potential_spam',
                'Rejected' => 'rejected',
                'Spam' => 'spam', 
                'Published' => 'published',
                ])
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('author')
            ->add('email')
            ->add('createdAt')
            ->add('conference')
            ->add('state')
        ;
    }

}
