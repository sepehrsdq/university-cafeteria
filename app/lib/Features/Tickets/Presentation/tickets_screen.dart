import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/tickets_controller.dart';
import 'package:uni_project/Features/Tickets/Presentation/Widgets/ticket_create_screen.dart';
import 'package:uni_project/Features/Tickets/Presentation/Widgets/ticket_details_screen.dart';

class TicketsScreen extends StatelessWidget {
  final TicketsController controller = Get.put(TicketsController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text("تیکت‌های من", style: TextStyle(fontSize: 18)),
        centerTitle: true,
      ),
      body: Obx(() {
        if (controller.isLoading.value) {
          return Center(child: CircularProgressIndicator());
        }

        if (controller.tickets.isEmpty) {
          return Center(child: Text("شما هیچ تیکتی ندارید"));
        }

        return ListView.builder(
          itemCount: controller.tickets.length,
          itemBuilder: (context, index) {
            var ticket = controller.tickets[index];
            return Card(
              margin: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
              child: ListTile(
                title: Text(ticket['subject'],
                    style: TextStyle(fontWeight: FontWeight.bold)),
                subtitle: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(height: 5),
                    Text(
                        "وضعیت: ${ticket['status'] == 'open' ? 'باز' : 'بسته'}",
                        style: TextStyle(
                          color: ticket['status'] == 'open'
                              ? Colors.blue
                              : Colors.green,
                          fontWeight: FontWeight.w600,
                        )),
                  ],
                ),
                trailing: Icon(
                  ticket['status'] == 'open' ? Icons.chat : Icons.check_circle,
                  color:
                      ticket['status'] == 'open' ? Colors.blue : Colors.green,
                ),
                onTap: () {
                  Get.to(() => TicketDetailsScreen(), arguments: ticket);
                },
              ),
            );
          },
        );
      }),
      floatingActionButton: FloatingActionButton(
        onPressed: () {
          Get.to(() => TicketCreateScreen());
        },
        child: Icon(Icons.add),
        tooltip: "ایجاد تیکت جدید",
      ),
    );
  }
}
