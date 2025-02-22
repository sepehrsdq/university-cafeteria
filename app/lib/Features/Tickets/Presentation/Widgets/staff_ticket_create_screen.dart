import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/staff_ticket_create_controller.dart';

class StaffTicketCreateScreen extends StatelessWidget {
  final StaffTicketCreateController controller =
      Get.put(StaffTicketCreateController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("ایجاد تیکت جدید", style: TextStyle(fontSize: 18)),
        centerTitle: true,
      ),
      body: Padding(
        padding: EdgeInsets.all(16.0),
        child: Column(
          children: [
            // انتخاب دانشجو
            Obx(() {
              if (controller.isLoadingStudents.value) {
                return Center(child: CircularProgressIndicator());
              }

              return DropdownButtonFormField(
                decoration: InputDecoration(
                  labelText: "انتخاب دانشجو",
                  border: OutlineInputBorder(),
                ),
                value: controller.selectedStudentId.value == 0
                    ? null
                    : controller.selectedStudentId.value,
                items: controller.studentList.map((student) {
                  return DropdownMenuItem(
                    value: student['id'],
                    child: Text(student['name']),
                  );
                }).toList(),
                onChanged: (value) {
                  controller.selectedStudentId.value = value as int;
                },
              );
            }),

            SizedBox(height: 16),

            TextField(
              controller: controller.subjectController,
              decoration: InputDecoration(
                labelText: "موضوع تیکت",
                border: OutlineInputBorder(),
              ),
            ),

            SizedBox(height: 16),

            TextField(
              controller: controller.messageController,
              maxLines: 4,
              decoration: InputDecoration(
                labelText: "متن پیام",
                border: OutlineInputBorder(),
              ),
            ),

            SizedBox(height: 20),

            ElevatedButton(
              onPressed: () => controller.createTicket(),
              child: Text("ارسال تیکت"),
            ),
          ],
        ),
      ),
    );
  }
}
