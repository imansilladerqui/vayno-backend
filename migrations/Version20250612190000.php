<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250612190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial Vayno schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(255) NOT NULL, hashed_password VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');

        $this->addSql('CREATE TABLE parking_lots (id UUID NOT NULL, owner_id UUID NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(500) NOT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_PARKING_LOTS_OWNER ON parking_lots (owner_id)');
        $this->addSql('ALTER TABLE parking_lots ADD CONSTRAINT FK_PARKING_LOTS_OWNER FOREIGN KEY (owner_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE parking_slots (id UUID NOT NULL, lot_id UUID NOT NULL, slot_number VARCHAR(50) NOT NULL, slot_type VARCHAR(20) NOT NULL, price_per_hour DOUBLE PRECISION NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_PARKING_SLOTS_LOT ON parking_slots (lot_id)');
        $this->addSql('ALTER TABLE parking_slots ADD CONSTRAINT FK_PARKING_SLOTS_LOT FOREIGN KEY (lot_id) REFERENCES parking_lots (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE slot_availability (id UUID NOT NULL, slot_id UUID NOT NULL, date DATE DEFAULT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, is_recurring BOOLEAN NOT NULL, weekday INT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_SLOT_AVAILABILITY_SLOT ON slot_availability (slot_id)');
        $this->addSql('ALTER TABLE slot_availability ADD CONSTRAINT FK_SLOT_AVAILABILITY_SLOT FOREIGN KEY (slot_id) REFERENCES parking_slots (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE reservations (id UUID NOT NULL, slot_id UUID NOT NULL, renter_id UUID NOT NULL, start_dt TIMESTAMP(0) WITH TIME ZONE NOT NULL, end_dt TIMESTAMP(0) WITH TIME ZONE NOT NULL, status VARCHAR(20) NOT NULL, total_price DOUBLE PRECISION DEFAULT NULL, qr_code VARCHAR(255) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_RESERVATIONS_QR ON reservations (qr_code)');
        $this->addSql('CREATE INDEX IDX_RESERVATIONS_SLOT ON reservations (slot_id)');
        $this->addSql('CREATE INDEX IDX_RESERVATIONS_RENTER ON reservations (renter_id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RESERVATIONS_SLOT FOREIGN KEY (slot_id) REFERENCES parking_slots (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT FK_RESERVATIONS_RENTER FOREIGN KEY (renter_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE check_events (id UUID NOT NULL, reservation_id UUID NOT NULL, event_type VARCHAR(20) NOT NULL, timestamp TIMESTAMP(0) WITH TIME ZONE NOT NULL, notes VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CHECK_EVENTS_RESERVATION ON check_events (reservation_id)');
        $this->addSql('ALTER TABLE check_events ADD CONSTRAINT FK_CHECK_EVENTS_RESERVATION FOREIGN KEY (reservation_id) REFERENCES reservations (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE check_events');
        $this->addSql('DROP TABLE reservations');
        $this->addSql('DROP TABLE slot_availability');
        $this->addSql('DROP TABLE parking_slots');
        $this->addSql('DROP TABLE parking_lots');
        $this->addSql('DROP TABLE users');
    }
}
