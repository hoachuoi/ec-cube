<?php

namespace Plugin\PluginHoliday\Entity;

use Doctrine\ORM\Mapping as ORM;
    /**
     * Config
     *
     * @ORM\Table(name="plg_plugin_holiday_data")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\HasLifecycleCallbacks()
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\Entity(repositoryClass="Plugin\PluginHoliday\Repository\HolidayRepository")
     */
    class Holiday
    {
        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;

        /**
         * @var string
         * @ORM\Column(name="holiday_message", type="string", length=255)
         */
        private $holiday_message;

        /**
         * @var string
         * @ORM\Column(name="holiday_date", type="date")
         */
        private $holiday_date;
        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @param string $name
         *
         * @return $this;
         */
        public function setName($name)
        {
            $this->name = $name;

            return $this;
        }

        /**
         * @return string
         */
        public function getHolidayMessage()
        {
            return $this->holiday_message;
        }

        /**
         * @param string $holiday_message
         * @return Holiday
         */
        public function setHolidayMessage($holiday_message)
        {
            $this->holiday_message = $holiday_message;

            return $this;
        }

        /**
         * @return string
         */
        public function getHolidayDate()
        {
            // dump($this->holiday_date);
            return $this->holiday_date;
        }

        /**
         * @param string $holiday_date
         * @return $this
         */
        public function setHolidayDate($holiday_date)
        {
            $this->holiday_date = $holiday_date;

            return $this;
        }
    }
