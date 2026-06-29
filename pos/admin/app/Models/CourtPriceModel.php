<?php namespace App\Models;
 
use CodeIgniter\Model;
 
class CourtPriceModel extends Model{
    protected $table = 'hy_court_cost';
    protected $primaryKey = 'id';
    protected $allowedFields = ['club_id', 'start_time','end_time','is_weekend', 'cost'];

    public function getCostData($time, $duration, $isWeekend) {
        // Convert the posted time and duration to timestamps
        $startTime = strtotime($time);
        $endTime = $startTime + ($duration * 60 * 60); // Duration in hours

        // Build the query to retrieve the applicable cost from the database
        $builder = $this->db->table('hy_court_cost');
        
        // If it's a weekend, filter by is_weekend = 1; otherwise, use is_weekend = 0
        if ($isWeekend) {
            $builder->where('is_weekend', '1');
        } else {
            $builder->where('is_weekend', '0');
        }

        // Fetch all time slots from the table
        $query = $builder->get();
        $courtPrices = $query->getResult();
        $cost = 0;
        foreach ($courtPrices as $courtPrice) {
            // Convert the start_time and end_time from string to timestamps
            $courtStartTime = strtotime($courtPrice->start_time);
            $courtEndTime = strtotime($courtPrice->end_time);

            // If the end time is earlier than the start time, we assume it's overnight, so add 24 hours to the end time
            if ($courtEndTime < $courtStartTime) {
                $courtEndTime += 24 * 60 * 60; // Add 24 hours to the end_time
            }

            // Check if the posted time (and duration) falls within the court price time range
            if ($startTime >= $courtStartTime && $endTime <= $courtEndTime) {
                // If the time falls within the range, return the cost
                $cost = $courtPrice->cost;
                // return $courtPrice->cost;
            }
        }
        // If no matching cost is found, return an error message
        return $cost;
    }


}