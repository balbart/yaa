import requests
import json
from mac import Mac


class Yandex:
    token = ''

    def __init__(self, token):
        self.token = token
        self.headers = {
            "Authorization": "Bearer " + token
        }

    def get_token(self):
        return self.token

    def set_token(self, token):
        self.token = token

    def get_segments(self):
        params = {'Content-Type': 'application/json'}
        response = requests.get('https://api-audience.yandex.ru/v1/management/segments', params=params, headers=self.headers)
        return response.json()

    def create_segment(self, segment_name, filename):
        mac_list = Mac.txt2list(filename)
        body = self.create_body(mac_list)
        request = self.upload_segment(body)
        request = self.confirm_segment(segment_name, request)
        return request

    def upload_segment(self, body):
        headers = self.headers.copy()
        headers["Content-Type"] = "multipart/form-data; boundary=------------------------5b2a52c5c90f668a"
        headers['Context-Length'] = str(len(body))
        response = requests.post('https://api-audience.yandex.ru/v1/management/segments/upload_csv_file',
                                 headers=headers, data=body)
        return response.json()

    def create_body(self, list_of_macs):
        dispos = "--------------------------5b2a52c5c90f668a"
        body = dispos + '\r\n'
        body += 'Content-Disposition: form-data; name="file"; filename="data.csv"\r\n'
        body += 'Content-Type: application/octet-stream\r\n'
        for mac in list_of_macs:
            body += "\r\n" + mac
        body += '\r\n' + dispos + "--\r\n"
        return body

    def confirm_segment(self, segment_name, upload_request):
        segment_id = upload_request['segment']['id']
        confirm_dict = {
            'segment': {
                "id": segment_id,
                "name": segment_name,
                "hashed": False,
                "content_type": "mac"
            }
        }
        confirm_json = json.dumps(confirm_dict)
        request = requests.post('https://api-audience.yandex.ru/v1/management/segment/{}/confirm'.format(segment_id), headers=self.headers, data=confirm_json)
        return request


if __name__ == "__main__":
    y = Yandex(token)
    r = y.create_segment("offspring", "new_uniq_macs.txt")
    print(r)