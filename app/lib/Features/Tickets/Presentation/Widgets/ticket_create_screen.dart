import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/ticket_create_controller.dart';

class TicketCreateScreen extends StatelessWidget {
  final TicketCreateController controller = Get.put(TicketCreateController());

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
            // انتخاب کارمند
            Obx(() {
              if (controller.isLoadingStaff.value) {
                return Center(child: CircularProgressIndicator());
              }

              return DropdownButtonFormField(
                decoration: InputDecoration(
                  labelText: "انتخاب کارمند",
                  border: OutlineInputBorder(),
                ),
                value: controller.selectedStaffId.value == 0
                    ? null
                    : controller.selectedStaffId.value,
                items: controller.staffList.map((staff) {
                  return DropdownMenuItem(
                    value: staff['id'],
                    child: Text(staff['name']),
                  );
                }).toList(),
                onChanged: (value) {
                  controller.selectedStaffId.value = value as int;
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
