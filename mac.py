class Mac:
    @staticmethod
    def txt2list(filename):
        with open(filename, 'r', encoding='utf8') as read_file:
            macs_from_txt = list(map(lambda x: x.strip(), read_file.readlines()))
            return macs_from_txt