import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/staff_tickets_controller.dart';
import 'package:uni_project/Features/Tickets/Presentation/Widgets/staff_ticket_details_screen.dart';
import 'package:uni_project/Features/Tickets/Presentation/Widgets/staff_ticket_create_screen.dart';

class StaffTicketsScreen extends StatelessWidget {
  final StaffTicketsController controller = Get.put(StaffTicketsController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("تیکت‌های دانشجویان", style: TextStyle(fontSize: 18)),
        centerTitle: true,
      ),
      body: Obx(() {
        if (controller.isLoading.value) {
          return Center(child: CircularProgressIndicator());
        }

        if (controller.tickets.isEmpty) {
          return Center(child: Text("هیچ تیکتی موجود نیست"));
        }

        return ListView.builder(
          itemCount: controller.tickets.length,
          itemBuilder: (context, index) {
            var ticket = controller.tickets[index];
            return Card(
              child: ListTile(
                title: Text(ticket['subject']),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text("دانشجو: ${ticket['student_name']}"),
                    Text("شماره دانشجویی: ${ticket['student_number']}"),
                    Text(
                        "وضعیت: ${ticket['status'] == 'open' ? 'باز' : 'بسته'}"),
                  ],
                ),
                onTap: () {
                  Get.to(() => StaffTicketDetailsScreen(), arguments: ticket);
                },
              ),
            );
          },
        );
      }),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Get.to(() => StaffTicketCreateScreen());
        },
        child: Icon(Icons.add),
        tooltip: "ایجاد تیکت جدید",
      ),
    );
  }
}
