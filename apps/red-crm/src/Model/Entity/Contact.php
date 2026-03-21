<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Contact Entity
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $notes
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $updated
 * @property string $deleted
 */
class Contact extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'email' => true,
        'phone' => true,
        'notes' => true,
        'created' => true,
        'updated' => true,
        'deleted' => true,
    ];
}
