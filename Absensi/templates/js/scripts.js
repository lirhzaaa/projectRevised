// Inisialisasi flatpickr pada elemen input tanggal
document.addEventListener('DOMContentLoaded', function () {
    flatpickr('.datetimepicker', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
    });
    fetchRecords(); // Fetch records when the document is loaded
});

// Global variable to store records data
let globalRecordsData = [];

// Function to expose data to other scripts
function getGlobalRecordsData() {
    return globalRecordsData;
}

function fetchRecords() {
    // Declare filter element variables at the beginning of the function
    let idFilterElement, userIdFilterElement, attendanceStatusFilterElement, nameFilterElement, lateFilterElement; 
    // Get references to filter elements
    idFilterElement = document.getElementById('idFilter');
    userIdFilterElement = document.getElementById('userIdFilter');
    attendanceStatusFilterElement = document.getElementById('attendance_statusFilter');
    nameFilterElement = document.getElementById('nameFilter');
    lateFilterElement = document.getElementById('lateFilter');
    
    // Get filter values, handling null cases
    const idFilter = idFilterElement ? idFilterElement.value : ''; 
    const userIdFilter = userIdFilterElement ? userIdFilterElement.value : '';
    const attendance_statusFilter = attendanceStatusFilterElement ? attendanceStatusFilterElement.value : '';
    const nameFilter = nameFilterElement ? nameFilterElement.value : '';
    const lateFilter = lateFilterElement ? lateFilterElement.value : '';

    fetch(`../fetch_records.php?id=${idFilter}&user_id=${userIdFilter}&attendance_status=${attendance_statusFilter}&name=${nameFilter}&is_late=${lateFilter}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok'); 
            }
            return response.json(); 
        })
        .then(data => {
            console.log('Fetched data:', data);   

            globalRecordsData = data; // Store data globally
            displayRecords(data);
        })
        .catch(error => {
            console.error('Error fetching or parsing records:', error);
            // Display a user-friendly error message on the page
            document.getElementById('records').innerHTML = '<div class="alert alert-danger">An error occurred while fetching attendance_status records.</div>';
        });
}

function displayRecords(data) {
    const recordsDiv = document.getElementById('records');
    let table = '<table class="table table-bordered"><tr><th>ID</th><th>User ID</th><th>Full Name</th><th>Check In</th><th>Check Out</th><th>Attendance Status</th><th>Late Status (In/Out)</th><th>Actions</th></tr>';

    // Initialize objects to store grouped data
    const groupedData = {};

    data.forEach(record => {
        const date = record.datetime.split(' ')[0]; // Extract date part
        const key = `${record.user_id}_${date}`;
        
        if (!groupedData[key]) {
            groupedData[key] = {
                id: null, // Default to null, to be set later
                user_id: record.user_id,
                full_name: record.full_name,
                date: date,
                check_in: null,
                check_out: null,
                attendance_status: null,
                is_late: null
            };
        }
        
        if (record.check_type === 0) { // Check-in
            groupedData[key].check_in = record.datetime;
            groupedData[key].is_late = record.is_late; // Assume late status is associated with check-in
        } else if (record.check_type === 1) { // Check-out
            groupedData[key].check_out = record.datetime;
        }

        // Set ID to the latest record ID (assuming this is how you want to handle it)
        groupedData[key].id = record.id;
    });

    // Update attendance status and create table rows
    Object.values(groupedData).forEach(record => {
        if (record.check_in && record.check_out) {
            record.attendance_status = 'Present';
        } else if (record.check_in) {
            record.attendance_status = 'Belum Checkout';
        } else if (record.check_out) {
            record.attendance_status = 'Belum Checkin'; // Added status for check-out without check-in
        } else {
            record.attendance_status = 'Absent';
        }

        // Check if check-in is late (if available)
        let isLateIn = record.check_in ? isLate(new Date(record.check_in), true) : '-';
        // Check-out status always "-"
        let isLateOut = '-';

        table += `<tr>
            <td>${record.id}</td> 
            <td><input type="text" value="${record.user_id}" id="user_id_${record.id}" class="form-control"></td>
            <td>${record.full_name}</td>
            <td><input type="datetime-local" value="${record.check_in ? record.check_in.replace(' ', 'T') : ''}" id="datetime_${record.id}_in" class="form-control datetimepicker"></td>
            <td><input type="datetime-local" value="${record.check_out ? record.check_out.replace(' ', 'T') : ''}" id="datetime_${record.id}_out" class="form-control datetimepicker"></td>
            <td>
                <select id="attendance_status_${record.id}" class="form-control">
                    <option value="0" ${record.attendance_status === 'Absent' ? 'selected' : ''}>Absent</option>
                    <option value="1" ${record.attendance_status === 'Present' ? 'selected' : ''}>Present</option>
                    <option value="2" ${record.attendance_status === 'Izin' ? 'selected' : ''}>Izin</option>
                    <option value="3" ${record.attendance_status === 'Sakit' ? 'selected' : ''}>Sakit</option>
                    <option value="4" ${record.attendance_status === 'Alfa' ? 'selected' : ''}>Alfa</option>
                    <option value="5" ${record.attendance_status === 'Belum Checkout' ? 'selected' : ''}>Belum Checkout</option>
                    <option value="6" ${record.attendance_status === 'Belum Checkin' ? 'selected' : ''}>Belum Checkin</option>
                </select>
            </td>
            <td>${isLateIn} / ${isLateOut}</td> 
            <td>
                <button onclick="saveRecord(${record.id})" class="btn btn-primary"> 
                    <i class="fas fa-pen"></i> Save Change
                </button>
            </td>
        </tr>`;
    });

    if (Object.keys(groupedData).length === 0) {
        // No records found, display a message
        table += '<tr><td colspan="8" class="text-center">No records found.</td></tr>';
    }

    table += '</table>';
    recordsDiv.innerHTML = table;

    // Re-initialize flatpickr
    flatpickr('.datetimepicker', {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
    });
}

function isLate(datetime, isCheckIn = true) {
    const thresholdHour = 7;
    const thresholdMinute = 30;

    const date = new Date(datetime);
    const thresholdTime = new Date(date.getFullYear(), date.getMonth(), date.getDate(), thresholdHour, thresholdMinute);

    if (isCheckIn) {
        return date > thresholdTime ? 'Late' : 'On Time';
    } else {
        return '-'; // No late status for check-out
    }
}

function saveRecord(id) {
    const userId = document.getElementById(`user_id_${id}`).value;
    const datetimeIn = document.getElementById(`datetime_${id}_in`).value.replace('T', ' ');
    const datetimeOut = document.getElementById(`datetime_${id}_out`).value.replace('T', ' ');
    const attendanceStatus = document.getElementById(`attendance_status_${id}`).value;

    console.log(`Saving record with ID: ${id}, DateTime In: ${datetimeIn}, DateTime Out: ${datetimeOut}, attendance_status Status: ${attendanceStatus}`);

    if (!userId || !attendanceStatus) {
        alert('User ID and attendance_status Status must be filled.');
        return;
    }

    // Determine late status for check-in and check-out
    const isLateIn = isLate(new Date(datetimeIn)) ? 1 : 0;
    const isLateOut = datetimeOut ? (isLate(new Date(datetimeOut)) ? 1 : 0) : 0; // 0 if no check-out

    const formData = new FormData();
    formData.append('id', id);
    formData.append('user_id', userId);
    formData.append('datetime_in', datetimeIn);
    formData.append('datetime_out', datetimeOut);
    formData.append('attendance_status', attendanceStatus);
    formData.append('is_late_in', isLateIn);
    formData.append('is_late_out', isLateOut);

    fetch('../manage_record.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        if (text === 'success') {
            alert('Record updated successfully');
            fetchRecords(); // Refresh records after saving
        } else {
            alert('Failed to update record. Server response: ' + text); 
        }
    })
    .catch(error => {
        console.error('Error saving record:', error);
        alert('An error occurred while saving the record. Please try again later.'); 
    });
}

function deleteRecords(type) {
    if (!confirm('Are you sure you want to delete all records?')) {
        return; // Exit if user cancels the action
    }

    let formData = new FormData();
    formData.append('action', 'delete_all');
    formData.append('type', type);

    fetch('../manage_attendance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.status === 'success') {
                alert('All records deleted successfully.');
                fetchRecords(); // Refresh records after deletion
            } else {
                alert('Delete failed: ' + data.message);
            }
        } catch (error) {
            console.error('Error parsing JSON:', error);
            console.error('Response text:', text); // Log the raw response
        }
    })
    .catch(error => console.error('Error:', error));
}
