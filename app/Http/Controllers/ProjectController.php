<?php
namespace App\Http\Controllers;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Models\EmployeeProjectRecord;

class ProjectController extends Controller
{
    public function getEmployeeProjects(Request $request)
    {
        $employeeId = $request->user()->id;

        $projects = Project::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
        ->with(['area', 'zones.pattern', 'zones.shifts'])
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $projects
        ]);
    }
    public function getEmployeeProjectRecords(Request $request)
    {
        // الحصول على الموظف من التوكن
        $employee = auth()->user();
    
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }
    
        // استرجاع سجلات مشاريع الموظف مع العلاقات المطلوبة
        $projectRecords = EmployeeProjectRecord::with([
            'project.area',        // مشروع مع المنطقة
            'zone.pattern',        // الموقع مع النمط
            'shift'                // الوردية
        ])
        ->where('employee_id', $employee->id) // تحديد الموظف
        ->where('status', '1')
        ->get();
    
        // بناء الاستجابة
        $response = $projectRecords->map(function ($record) {
            return [
                'record_id' => $record->id,
                'project' => [
                    'id' => $record->project->id,
                    'name' => $record->project->name,
                    'description' => $record->project->description,
                    'area' => [
                        'id' => $record->project->area->id ?? null,
                        'name' => $record->project->area->name ?? null,
                    ],
                    'start_date' => $record->project->start_date,
                    'end_date' => $record->project->end_date,
                ],
                'zone' => [
                    'id' => $record->zone->id ?? null,
                    'name' => $record->zone->name ?? null,
                    'start_date' => $record->zone->start_date ?? null,
                    'pattern' => $record->zone->pattern ? [
                        'id' => $record->zone->pattern->id,
                        'name' => $record->zone->pattern->name,
                        'working_days' => $record->zone->pattern->working_days,
                        'off_days' => $record->zone->pattern->off_days,
                        'hours_cat' => $record->zone->pattern->hours_cat,
                    ] : null,
                    'lat' => $record->zone->lat ?? null,
                    'longg' => $record->zone->longg ?? null,
                    'area' => $record->zone->area ?? null,
                ],
                'shift' => $record->shift ? [
                    'id' => $record->shift->id,
                    'name' => $record->shift->name,
                    'type' => $record->shift->type,
                    'morning_start' => $record->shift->morning_start,
                    'morning_end' => $record->shift->morning_end,
                    'evening_start' => $record->shift->evening_start,
                    'evening_end' => $record->shift->evening_end,
                    'early_entry_time' => $record->shift->early_entry_time,
                    'last_entry_time' => $record->shift->last_entry_time,
                    'early_exit_time' => $record->shift->early_exit_time,
                    'last_time_out' => $record->shift->last_time_out,
                    'start_date' => $record->shift->start_date,
                ] : null,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'status' => $record->status,
            ];
        });
    
        return response()->json([
            'status' => 'success',
            'data' => $response,
        ]);
    }
    
    
}
