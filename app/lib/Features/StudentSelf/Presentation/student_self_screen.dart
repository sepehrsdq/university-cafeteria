import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/StudentSelf/Presentation/Controllers/student_self_controller.dart';

class StudentSelfScreen extends StatelessWidget {
  final StudentSelfController controller = Get.put(StudentSelfController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('رزروهای امروز', style: TextStyle(fontSize: 18)),
        centerTitle: true,
        leading: IconButton(
          onPressed: controller.routeMainPage,
          icon: const Icon(Icons.keyboard_backspace),
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'جستجو بر اساس نام یا شماره دانشجویی',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => controller.search(value),
            ),
          ),
          Expanded(
            child: Obx(() {
              if (controller.isLoading.value) {
                return Center(child: CircularProgressIndicator());
              }

              if (controller.reservations.isEmpty) {
                return Center(child: Text('هیچ رزروی یافت نشد!'));
              }

              return ListView.builder(
                itemCount: controller.reservations.length,
                itemBuilder: (context, index) {
                  var res = controller.reservations[index];
                  return Card(
                    margin: EdgeInsets.all(8.0),
                    child: ListTile(
                      title: Text('${res['student_name']}'),
                      subtitle: Text(
                        'شماره دانشجویی: ${res['student_number']} \n'
                        'غذا: ${res['meal_name']} \n'
                        'نوع: ${controller.convertServeType(res['serve_type'])}',
                      ),
                      trailing: Text(
                        controller.convertToShamsi(
                            res['created_at']), // نمایش تاریخ شمسی
                        style: TextStyle(fontWeight: FontWeight.bold),
                      ),
                    ),
                  );
                },
              );
            }),
          ),
        ],
      ),
    );
  }
}
