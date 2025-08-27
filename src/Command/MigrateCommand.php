<?php

namespace Muz\Pimcore\SoMLBundle\Command;

use Pimcore\Console\AbstractCommand;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Service;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends AbstractCommand
{
    protected static $defaultName = 'muz:social-media:migrate';
    protected static $defaultDescription = 'Creates or updates the SocialMediaPost class definition';

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating/Updating SocialMediaPost class definition');

        try {
            $this->createSocialMediaPostClass();
            $io->success('SocialMediaPost class definition created/updated successfully');
            return 0;
        } catch (\Exception $e) {
            $io->error('Error creating/updating SocialMediaPost class definition: ' . $e->getMessage());
            return 1;
        }
    }

    private function createSocialMediaPostClass(): void
    {
        // Check if class already exists
        $class = ClassDefinition::getByName('SocialMediaPost');
        if (!$class) {
            $class = new ClassDefinition();
            $class->setName('SocialMediaPost');
            $class->setGroup('Social Media');
        }

        // Define the SocialMediaPost class
        $classDefinition = [
            "name" => "SocialMediaPost",
            "title" => "Social Media Post",
            "description" => "Social media posts imported from various platforms",
            "group" => "Social Media",
            "linkGeneratorReference" => "",
            "compositeIndices" => [],
            "showAppLoggerTab" => false,
            "propertyVisibility" => [
                "grid" => [
                    "id" => true,
                    "key" => true,
                    "path" => true,
                    "published" => true,
                    "modificationDate" => true,
                    "creationDate" => true
                ],
                "search" => [
                    "id" => true,
                    "key" => true,
                    "path" => true,
                    "published" => true,
                    "modificationDate" => true,
                    "creationDate" => true
                ]
            ],
            "enableGridLocking" => false,
            "layoutDefinitions" => $this->getSocialMediaPostLayout()
        ];

        $jsonDefinition = json_encode($classDefinition);
        $class->setDescription($classDefinition['description']);
        $class->setPropertyVisibility($classDefinition['propertyVisibility']);

        Service::importClassDefinitionFromJson($class, $jsonDefinition, true);
        $class->save();
    }

    private function getSocialMediaPostLayout()
    {
        return [
            "fieldtype" => "panel",
            "labelWidth" => 100,
            "layout" => null,
            "name" => "pimcore_root",
            "type" => null,
            "region" => null,
            "title" => null,
            "width" => null,
            "height" => null,
            "collapsible" => false,
            "collapsed" => false,
            "bodyStyle" => null,
            "datatype" => "layout",
            "permissions" => null,
            "children" => [
                [
                    "fieldtype" => "tabpanel",
                    "name" => "Layout",
                    "type" => null,
                    "region" => null,
                    "title" => null,
                    "width" => null,
                    "height" => null,
                    "collapsible" => false,
                    "collapsed" => false,
                    "bodyStyle" => null,
                    "datatype" => "layout",
                    "permissions" => null,
                    "children" => [
                        [
                            "fieldtype" => "panel",
                            "labelWidth" => 100,
                            "layout" => null,
                            "name" => "Post Info",
                            "type" => null,
                            "region" => null,
                            "title" => "Post Info",
                            "width" => null,
                            "height" => null,
                            "collapsible" => false,
                            "collapsed" => false,
                            "bodyStyle" => "",
                            "datatype" => "layout",
                            "permissions" => null,
                            "children" => [
                                [
                                    "fieldtype" => "input",
                                    "width" => null,
                                    "defaultValue" => null,
                                    "columnLength" => 190,
                                    "regex" => "",
                                    "regexFlags" => [],
                                    "unique" => false,
                                    "showCharCount" => false,
                                    "name" => "externalId",
                                    "title" => "External ID",
                                    "tooltip" => "",
                                    "mandatory" => true,
                                    "noteditable" => true,
                                    "index" => true,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => true,
                                    "defaultValueGenerator" => ""
                                ],
                                [
                                    "fieldtype" => "select",
                                    "options" => [
                                        [
                                            "key" => "Twitter",
                                            "value" => "twitter"
                                        ],
                                        [
                                            "key" => "Instagram",
                                            "value" => "instagram"
                                        ],
                                        [
                                            "key" => "Facebook",
                                            "value" => "facebook"
                                        ],
                                        [
                                            "key" => "LinkedIn",
                                            "value" => "linkedin"
                                        ]
                                    ],
                                    "width" => "",
                                    "defaultValue" => "",
                                    "optionsProviderClass" => "",
                                    "optionsProviderData" => "",
                                    "columnLength" => 190,
                                    "dynamicOptions" => false,
                                    "name" => "platform",
                                    "title" => "Platform",
                                    "tooltip" => "",
                                    "mandatory" => true,
                                    "noteditable" => false,
                                    "index" => true,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => true
                                ],
                                [
                                    "fieldtype" => "numeric",
                                    "width" => 200,
                                    "defaultValue" => 0,
                                    "integer" => true,
                                    "unsigned" => false,
                                    "minValue" => -1,
                                    "maxValue" => 99,
                                    "unique" => false,
                                    "decimalSize" => null,
                                    "decimalPrecision" => null,
                                    "name" => "sortPriority",
                                    "title" => "Sort Priority",
                                    "tooltip" => "Lower numbers appear first (99 = highest priority, -1 = last in list)",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => true,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => false
                                ],
                                [
                                    "fieldtype" => "textarea",
                                    "width" => "",
                                    "height" => "",
                                    "maxLength" => null,
                                    "showCharCount" => false,
                                    "excludeFromSearchIndex" => false,
                                    "name" => "content",
                                    "title" => "Content",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => true
                                ],
                                [
                                    "fieldtype" => "datetime",
                                    "queryColumnType" => "bigint(20)",
                                    "columnType" => "bigint(20)",
                                    "defaultValue" => null,
                                    "useCurrentDate" => false,
                                    "name" => "publishedAt",
                                    "title" => "Published At",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => true,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => true
                                ],
                                [
                                    "fieldtype" => "input",
                                    "width" => null,
                                    "defaultValue" => null,
                                    "columnLength" => 190,
                                    "regex" => "",
                                    "regexFlags" => [],
                                    "unique" => false,
                                    "showCharCount" => false,
                                    "name" => "url",
                                    "title" => "URL",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => false,
                                    "visibleSearch" => false,
                                    "defaultValueGenerator" => ""
                                ],
                                [
                                    "fieldtype" => "input",
                                    "width" => null,
                                    "defaultValue" => null,
                                    "columnLength" => 190,
                                    "regex" => "",
                                    "regexFlags" => [],
                                    "unique" => false,
                                    "showCharCount" => false,
                                    "name" => "hashtags",
                                    "title" => "Hashtags",
                                    "tooltip" => "Comma separated list of hashtags",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => true,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => true,
                                    "defaultValueGenerator" => ""
                                ],
                                [
                                    "fieldtype" => "input",
                                    "width" => null,
                                    "defaultValue" => null,
                                    "columnLength" => 190,
                                    "regex" => "",
                                    "regexFlags" => [],
                                    "unique" => false,
                                    "showCharCount" => false,
                                    "name" => "mentions",
                                    "title" => "Mentions",
                                    "tooltip" => "Comma separated list of mentioned users",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => false,
                                    "visibleSearch" => false,
                                    "defaultValueGenerator" => ""
                                ]
                            ]
                        ],
                        [
                            "fieldtype" => "panel",
                            "labelWidth" => 100,
                            "layout" => null,
                            "name" => "Media",
                            "type" => null,
                            "region" => null,
                            "title" => "Media",
                            "width" => null,
                            "height" => null,
                            "collapsible" => false,
                            "collapsed" => false,
                            "bodyStyle" => "",
                            "datatype" => "layout",
                            "permissions" => null,
                            "children" => [
                                [
                                    "fieldtype" => "manyToManyRelation",
                                    'assetsAllowed' => true,
                                    'objectsAllowed' => false,
                                    'documentsAllowed' => false,
                                    'assetTypes' => ['image', 'video'],
                                    "width" => "",
                                    "height" => "",
                                    "relationType" => true,
                                    "visibleFields" => "filename,filesize",
                                    "allowToCreateNewObject" => false,
                                    "optimizedAdminLoading" => false,
                                    "enableTextSelection" => false,
                                    "visibleFieldDefinitions" => [],
                                    "classes" => [],
                                    "pathFormatterClass" => "",
                                    "name" => "mediaAssets",
                                    "title" => "Media",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "invisible" => false,
                                    "visibleGridView" => false,
                                    "visibleSearch" => false
                                ]
                            ]
                        ],
                        [
                            "fieldtype" => "panel",
                            "labelWidth" => 100,
                            "layout" => null,
                            "name" => "Metrics",
                            "type" => null,
                            "region" => null,
                            "title" => "Metrics",
                            "width" => null,
                            "height" => null,
                            "collapsible" => false,
                            "collapsed" => false,
                            "bodyStyle" => "",
                            "datatype" => "layout",
                            "permissions" => null,
                            "children" => [
                                [
                                    "fieldtype" => "numeric",
                                    "width" => "",
                                    "defaultValue" => null,
                                    "integer" => true,
                                    "unsigned" => true,
                                    "minValue" => null,
                                    "maxValue" => null,
                                    "unique" => false,
                                    "decimalSize" => null,
                                    "decimalPrecision" => null,
                                    "name" => "likeCount",
                                    "title" => "Like Count",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => false,
                                    "defaultValueGenerator" => ""
                                ],
                                [
                                    "fieldtype" => "numeric",
                                    "width" => "",
                                    "defaultValue" => null,
                                    "integer" => true,
                                    "unsigned" => true,
                                    "minValue" => null,
                                    "maxValue" => null,
                                    "unique" => false,
                                    "decimalSize" => null,
                                    "decimalPrecision" => null,
                                    "name" => "shareCount",
                                    "title" => "Share Count",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => false,
                                    "defaultValueGenerator" => ""
                                ],
                                [
                                    "fieldtype" => "numeric",
                                    "width" => "",
                                    "defaultValue" => null,
                                    "integer" => true,
                                    "unsigned" => true,
                                    "minValue" => null,
                                    "maxValue" => null,
                                    "unique" => false,
                                    "decimalSize" => null,
                                    "decimalPrecision" => null,
                                    "name" => "commentCount",
                                    "title" => "Comment Count",
                                    "tooltip" => "",
                                    "mandatory" => false,
                                    "noteditable" => false,
                                    "index" => false,
                                    "locked" => false,
                                    "style" => "",
                                    "permissions" => null,
                                    "datatype" => "data",
                                    "relationType" => false,
                                    "invisible" => false,
                                    "visibleGridView" => true,
                                    "visibleSearch" => false,
                                    "defaultValueGenerator" => ""
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
