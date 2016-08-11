<?php

namespace Gini\ORM;

class Equipment extends YiQiKong\RObject
{
    public $source_name     = 'string:10';
    public $source_id       = 'int,default:0';
    public $uuid            = 'string:100';
    public $device_id       = 'string:100';
    public $name            = 'string:100';
    public $icon            = 'string:*';

    public $institute       = 'RArray';
    public $ref_no          = 'string:50';

    public $model           = 'string:50';
    public $spec            = 'string:50';
    public $price           = 'double,default:0';

    public $manu_place      = 'string:100';
    public $manu_name       = 'string:100';
    public $manu_date       = 'datetime';

    public $purchased_date  = 'datetime';
    public $enroll_date     = 'datetime';

    public $tech_specs      = 'string:*';
    public $features        = 'string:*';
    public $accessories     = 'string:*';
    public $application     = 'string:*';

    public $contact_name    = 'string:50';
    public $contact_phone   = 'string:50';
    public $contact_email   = 'string:50';

    public $location        = 'string:100';
    public $longitude       = 'string:100';
    public $latitude        = 'string:100';

    public $can_reserv      = 'int,default:0';
    public $can_sample      = 'int,default:0';

    public $record_type     = 'int';
    public $record_unit     = 'double, default: 0';
    public $record_minimum  = 'double, default: 0';
    public $sample_type     = 'int';
    public $sample_unit     = 'double, default: 0';
    public $sample_minimum  = 'double, default: 0';

    public $alias_name      = 'string:50';
    public $en_name         = 'string:100';

    public $note            = 'string:*';
    public $weight          = 'int,default:0';
    public $share           = 'int,default:0';

    public $off_code        = 'string:100';


    public function convertRPCData(array $rdata) {
        $data = [];
        $data['id'] = $rdata['id'];
        $data['source_name'] = $rdata['source_name'];
        $data['source_id'] = $rdata['source_id'];
        $data['uuid'] = $rdata['uuid'];
        $data['device_id'] = $rdata['device_id'];
        $data['name'] = $rdata['name'];
        $data['icon'] = $rdata['icon'];

        $data['institute'] = $rdata['institute'];
        $data['ref_no'] = $rdata['ref_no'];

        $data['model'] = $rdata['model'];
        $data['spec'] = $rdata['spec'];
        $data['price'] = $rdata['price'];

        $data['manu_place'] = $rdata['manu_place'];
        $data['manu_name'] = $rdata['manu_name'];
        $data['manu_date'] = $rdata['manu_date'];

        $data['purchased_date'] = $rdata['purchased_date'];
        $data['enroll_date'] = $rdata['enroll_date'];

        $data['tech_specs'] = $rdata['tech_specs'];
        $data['features'] = $rdata['features'];
        $data['accessories'] = $rdata['accessories'];
        $data['application'] = $rdata['application'];

        $data['contact_name'] = $rdata['manu_place'];
        $data['contact_phone'] = $rdata['manu_name'];
        $data['contact_email'] = $rdata['manu_date'];

        $data['location'] = $rdata['location'];
        $data['longitude'] = $rdata['longitude'];
        $data['latitude'] = $rdata['latitude'];

        $data['can_reserv'] = $rdata['can_reserv'];
        $data['can_sample'] = $rdata['can_sample'];

        $data['record_type'] = $rdata['record_type'];
        $data['record_unit'] = $rdata['record_unit'];
        $data['record_minimum'] = $rdata['record_minimum'];
        $data['sample_type'] = $rdata['sample_type'];
        $data['sample_unit'] = $rdata['sample_unit'];
        $data['sample_minimum'] = $rdata['sample_minimum'];

        $data['alias_name'] = $rdata['alias_name'];
        $data['en_name'] = $rdata['en_name'];

        $data['note'] = $rdata['note'];
        $data['weight'] = $rdata['weight'];
        $data['share'] = $rdata['share'];

        $data['off_code'] = $rdata['off_code'];

        $data['_extra'] = J(
            array_diff_key($rdata, array_flip(
                    [
                        'id',
                        'source_name',
                        'source_id',
                        'uuid',
                        'device_id',
                        'name',
                        'icon',
                        'institute',
                        'ref_no',
                        'model',
                        'spec',
                        'price',
                        'manu_place',
                        'manu_name',
                        'manu_date',
                        'purchased_date',
                        'enroll_date',
                        'tech_specs',
                        'features',
                        'accessories',
                        'application',
                        'contact_name',
                        'contact_phone',
                        'contact_email',
                        'location',
                        'longitude',
                        'latitude',
                        'can_reserv',
                        'can_sample',
                        'record_type',
                        'record_unit',
                        'record_minimum',
                        'sample_type',
                        'sample_unit',
                        'sample_minimum',
                        'alias_name',
                        'en_name',
                        'note',
                        'weight',
                        'share',
                        'off_code',
                    ]
                )
            )
        );

        return $data;
    }

    public function fetchRPC($data)
    {
        try {
            $key = strtr("%name#%id", ['%name' => $this->name(), '%id' => $data]);
            $cache = \Gini\Cache::of('yiqikong');
            $equipment = $cache->get($key);
            if (is_array($equipment) && count($equipment) > 0) {
                return $equipment;
            }
            else {
                $rpc = self::getRPC('directory')->YiQiKong->Directory;
                $equipment = $rpc->getEquipment($data);
                if (is_array($equipment) && count($equipment) > 0) {
                    $cache->set($key, $equipment, 43200);
                }
                return $equipment;
            }
        } catch (\Gini\RPC\Exception $e) {
            return [];
        }
    }

}

